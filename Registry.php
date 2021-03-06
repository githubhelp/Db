<?php
/**
 * Fwk
 *
 * Copyright (c) 2011-2012, Julien Ballestracci <julien@nitronet.org>.
 * All rights reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * PHP Version 5.3
 *
 * @package    Fwk
 * @subpackage Db
 * @author     Julien Ballestracci <julien@nitronet.org>
 * @copyright  2011-2012 Julien Ballestracci <julien@nitronet.org>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link       http://www.phpfwk.com
 */
namespace Fwk\Db;

use Fwk\Events\Dispatcher,
    Fwk\Events\Event;

/**
 * Entity registry object
 *
 */
class Registry implements \Countable, \IteratorAggregate
{
    const STATE_NEW             = 'new';
    const STATE_FRESH           = 'fresh';
    const STATE_CHANGED         = 'changed';
    const STATE_UNKNOWN         = 'unknown';
    const STATE_UNREGISTERED    = 'unregistered';

    const ACTION_SAVE           = 'save';
    const ACTION_DELETE         = 'delete';

    /**
     * Storage object
     *
     * @var \SplObjectStorage
     */
    protected $store;

    /**
     * @var string
     */
    protected $tableName;

    /**
     * @var integer
     */
    protected $_priority    = \PHP_INT_MAX;

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct($tableName)
    {
        $this->store        = new \SplObjectStorage();
        $this->tableName    = $tableName;
    }

    /**
     * Stores an object into registry
     *
     * @param mixed $object
     *
     * @return Registry
     */
    public function store($object, array $identifiers = array(), $state = Registry::STATE_UNKNOWN, array $data = array())
    {
        if ($this->contains($object)) {
                return $this;
        }

        \ksort($identifiers);

        if (!count($identifiers)) {
            $identifiers = array('%hash%' => Accessor::factory($object)->hashCode());
        }

        $dispatcher = new \Fwk\Events\Dispatcher();
        $dispatcher->on(EntityEvents::AFTER_SAVE, array($this, 'getLastInsertId'));
        $dispatcher->addListener($object);

        $data       = array_merge(array(
            'className'     => get_class($object),
            'identifiers'          => $identifiers,
            'state'                => $state,
            'initial_values'       => array(),
            'ts_stored'     => \microtime(true),
            'ts_action'     => 0,
            'action'        => null,
            'dispatcher'    => $dispatcher
        ), $data);

        $this->store->attach($object, $data);

        return $this;
    }

    /**
     * Fetches an object
     *
     * @param  array    $identifiers
     * @return Registry
     */
    public function get(array $identifiers)
    {
        \ksort($identifiers);

        foreach ($this->store as $obj) {
               $data = $this->store->getInfo();
            if($data['identifiers'] == $identifiers)

                return $obj;
        }

        return null;
    }

    /**
     *
     * @return string
     */
    public function getTableName()
    {
        return $this->tableName;
    }

    /**
     *
     * @param  mixed $object
     * @return array
     */
    public function getData($object)
    {
        if(!$this->contains($object))
            throw new Exceptions\UnregisteredEntity(
                sprintf('Unregistered entity (%s)', \get_class($object)
            )
        );

        foreach ($this->store as $obj) {
            if ($obj === $object) {
                return $this->store->getInfo();
            }
        }

        return array();
    }

    /**
     *
     * @param  mixed $object
     * @return void
     */
    public function setData($object, array $data = array())
    {
        if(!$this->contains($object))

                return;

        foreach ($this->store as $obj) {
            if ($obj === $object) {
                return $this->store->setInfo($data);
            }
        }
    }

    /**
     *
     * @param mixed             $object
     * @param \Fwk\Events\Event $event
     */
    public function fireEvent($object, Event $event)
    {
        return $this->getEventDispatcher($object)->notify($event);
    }

    /**
     *
     * @return \Fwk\Events\Dispatcher
     */
    public function getEventDispatcher($object)
    {
        if(!$this->contains($object))
            throw new Exceptions\UnregisteredEntity(
                sprintf('Unregistered entity (%s)', \get_class($object)
            )
        );

        $data   = $this->getData($object);

        return $data['dispatcher'];
    }

    /**
     * Listener to fetch last insert ID on auto-increment columns
     *
     * @param  \Fwk\Events\Event $event
     * @return void
     */
    public function getLastInsertId(Event $event)
    {
        $connx  = $event->connection;
        $table  = $connx->table($this->getTableName());
        $obj    = $event->object;

        foreach ($table->getColumns() as $column) {
            if(!$column->isAutoIncrement())
                    continue;

            $column         = $column->getName();
            $access         = Accessor::factory($obj);

            $test           = $access->get($column);
            if(!empty($test))
                continue;

            $lastInsertId   = $connx->lastInsertId();
            $access->set($column, $lastInsertId);
            $this->defineInitialValues($obj);
        }
    }

    /**
     * Removes an object from the registry
     *
     * @param  mixed    $object
     * @return Registry
     */
    public function remove($object)
    {
        foreach ($this->store as $obj) {
            if ($object === $obj) {
                $this->store->detach($object);
                break;
            }
        }

        return $this;
    }

    /**
     * Removes an object from its identifiers
     *
     * @param  array    $identifiers
     * @return Registry
     */
    public function removeByIdentifiers(array $identifiers)
    {
        $obj    = $this->get($identifiers);
        if (null !== $obj) {
            $this->remove($obj);
        }

        return $this;
    }

    /**
     * Tells if the registry contains an instance of the object
     *
     * @param mixed $object
     */
    public function contains($object)
    {
        return $this->store->contains($object);
    }

    /**
     *
     * @param  mixed  $object
     * @return string
     */
    public function getState($object)
    {
        if ($this->contains($object)) {
            $data   = $this->getData($object);

            return $data['state'];
        }

        return self::STATE_UNREGISTERED;
    }

    /**
     * Mark current object values (Accessor) as initial values
     *
     * @param <type> $object
     */
    public function defineInitialValues($object)
    {
        $accessor   = new Accessor($object);
        $data       = $this->getData($object);
        $values     = $accessor->toArray(array($accessor, 'everythingAsArrayModifier'));

        $data['initial_values'] = $values;
        $data['state']          = Registry::STATE_FRESH;
        $this->setData($object, $data);

        $data['dispatcher']->notify(new Event(EntityEvents::FRESH));
    }

    /**
     *
     * @param  <type> $object
     * @return <type>
     */
    public function getChangedValues($object)
    {
        if(!$this->contains($object))
                throw new \RuntimeException (\sprintf ('Trying to access changed values of an unregistered object'));

        $accessor   = new Accessor($object);
        $data       = $this->getData($object);
        $values     = $accessor->toArray(array($accessor, 'everythingAsArrayModifier'));

        $diff       = array();
        foreach ($values as $key => $val) {
            if(!isset($data['initial_values'][$key]) || $data['initial_values'][$key] !== $val)
                $diff[$key] = $val;
        }

        if (count($diff) && $data['state'] == self::STATE_FRESH) {
            $data['state']  =   self::STATE_CHANGED;
            $this->setData($object, $data);
        } elseif (!count($diff) && $data['state'] == self::STATE_CHANGED) {
            $data['state']  =   self::STATE_UNKNOWN;
            $this->setData($object, $data);
        }

        return $diff;
    }

    /**
     * Tells if an object has changed since "defineInitialValues" was called
     *
     * @param mixed $object
     */
    public function isChanged($object)
    {
        $changes    = $this->getChangedValues($object);

        return ($this->getState($object) == self::STATE_CHANGED);
    }

    /**
     *
     * @param mixed $object
     */
    public function markForAction($object, $action)
    {
        $state  = $this->getState($object);
        if ($state == self::STATE_UNREGISTERED) {
            $this->store($object, array(), self::STATE_UNKNOWN);
        }
        $data   = $this->getData($object);
        $data['action']     = $action;
        $data['ts_action']  = $this->_priority;
        $this->_priority--;
        $this->setData($object, $data);
    }

    /**
     *
     * @return \SplPriorityQueue
     */
    public function getWorkersQueue()
    {
        $queue  = new \SplPriorityQueue();

        foreach ($this->store as $object) {
            $data   = $this->getData($object);
            $action = $data['action'];
            $ts     = $data['ts_action'];

            if(empty($action) || null === $ts)
                continue;

            $chg        = $this->getChangedValues($object);
            $access     = new Accessor($object);
            $relations  = $access->getRelations();
            foreach ($chg as $key => $value) {
                if(!\array_key_exists($key, $relations))
                        continue;

                $relation   = $relations[$key];
                $relation->setParent($object, $this->getEventDispatcher($object));
            }

            $priority   = $ts;
            switch ($action) {
                case self::ACTION_DELETE:
                    $worker     = new Workers\DeleteEntityWorker($object);
                    break;

                case self::ACTION_SAVE:
                    $worker     = new Workers\SaveEntityWorker($object);
                    break;

                default:
                    throw new \InvalidArgumentException(sprintf("Unknown registry action '%s'", $action));
            }

            $worker->setRegistry($this);
            $queue->insert($worker, $priority);
        }

        return $queue;
    }

    public function toArray()
    {
        $final= array();
        foreach ($this->store as $object) {
            $final[] = $object;
        }

        return $final;
    }

    /**
     *
     * @return integer
     */
    public function count()
    {
        return $this->store->count();
    }

    /**
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->toArray());
    }
    
    public function getStore() {
        return $this->store;
    }
}
