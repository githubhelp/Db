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


class AbstractSchema 
{
    /**
     * 
     * @var Connection
     */
    protected $connection;
    
    
    /**
     * Declared entities for tables
     * 
     * @var array
     */
    protected $entities;
    
    public function getConnection() {
        return $this->connection;
    }

    public function setConnection(Connection $connection) {
        $this->connection = $connection;
    }
    
     /**
     *
     * @param string $tableName
     * @param string $entityClass
     */
    public function declareEntity($tableName, $entityClass) {
        $this->entities[$tableName] = $entityClass;
    }

    /**
     *
     * @param string $tableName
     * @return string
     */
    public function getDeclaredEntity($tableName) {
        if(is_array($this->entities) && \array_key_exists($tableName, $this->entities))
                return $this->entities[$tableName];

        return '\stdClass';
    }
}