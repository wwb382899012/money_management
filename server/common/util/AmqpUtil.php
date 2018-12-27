<?php

/**
 * AMQP extension wrapper to communicate with RabbitMQ server
 * For More documentation please see:
 * https://github.com/pdezwart/php-amqp
 */

/**
 * @defgroup CAMQP
 * @ingroup AMQPModule
 * @version 1.0.1
 */
include_once(dirname(__FILE__) . "/amqp/CAMQPQueue.php");
include_once(dirname(__FILE__) . "/amqp/CAMQPExchange.php");

/**
 * @class AmqpUtil
 * @brief Use for comunicate with AMQP server
 * @details  Send and recieve messages. Implements Wrapper template.
 *
 * "A" - Team:
 * @author     Andrey Evsyukov <thaheless@gmail.com>
 * @author     Alexey Spiridonov <a.spiridonov@2gis.ru>
 * @author     Alexey Papulovskiy <a.papulovskiyv@2gis.ru>
 * @author     Alexander Biryukov <a.biryukov@2gis.ru>
 * @author     Alexander Radionov <alex.radionov@gmail.com>
 * @author     Andrey Trofimenko <a.trofimenko@2gis.ru>
 * @author     Artem Kudzev <a.kiudzev@2gis.ru>
 * @author     Alexey Ashurok <a.ashurok@2gis.ru>
 *
 * @link       http://www.2gis.ru
 * @copyright  2GIS
 * @license http://www.Modframework.com/license/
 *
 * Requirements:
 * --------------
 *  - Mod 1.1.x or above
 *  - Rabbit php library or AMQP PECL library
 *
 * Usage:
 * --------------
 *
 * To write a message into the MQ-Exchange:
 *
 *     Mod::App()->amqp->exchange('topic')->publish('some message','some.route');
 *
 *
 * To read a message from MQ-Queue:
 *
 *     Mod::App()->amqp->bind('topic', 'some_listener')->get();
 *
 */
class AmqpUtil
{
    private $_connection = null;

    /**
     * @var null|AMQPChannel
     */
    private $_channel = null;
    /**
     * @brief states if extension should work in fake mode
     * @details in case it is enabled - CAMQP will not perform real connection with
     * @var boolean
     */
    public $isFakeMode = false;

    /**
     * @brief Initialize component.
     * @details in case fakeMode is enabled loading fake Queue and Exchange classes
     */
    public function __construct($env = 'master')
    {
        $url = JmfUtil::getEnvConfig('ENV_MQ_' . strtoupper($env));
        $url = new UrlUtil($url);
        $this->_connection = new AMQPConnection(array(
            'host' => $url->getHost(),
            'vhost' => $url->getPath(),
            'port' => $url->getPort(),
            'login' => $url->getUsername(),
            'password' => $url->getPassword(),
        ));

        //Autoconnect for pecl extension
        if ($this->_connection->isConnected() == false) {
            $this->_connection->connect();
        }

        // init AMQP _channel
        $this->_channel = new AMQPChannel($this->_connection);
    }


    /**
     * @brief Declares a new Exchange on the broker
     * @param $name
     * @param $flags
     */
    public function declareExchange($name, $type = AMQP_EX_TYPE_DIRECT, $flags = AMQP_DURABLE)
    {
        $ex = new CAMQPExchange($this->_channel);
        $ex->setName($name);
        $ex->setType($type);
        $ex->setFlags($flags);
        $ex->declareExchange();
        return $ex;
    }

    /**
     * @brief Declares a new Queue on the broker
     * @param $name
     * @param $flags
     */
    public function declareQueue($name, $flags = AMQP_DURABLE)
    {
        $queue = new CAMQPQueue($this->_channel);
        $queue->setFlags($flags);
        $queue->setName($name);
        $queue->declareQueue();
        return $queue;
    }


    /**
     * @brief Binds a queue to specified exchange
     * @details Returns an instance of CAMQPQueue for queue an exchange is bind
     * @param $queue
     * @param $exchange
     * @param $routingKey
     */
    public function bind($queue, $exchange, $routingKey = "")
    {
        $queue = $this->queue($queue);
        $queue->bind($exchange, $routingKey);
        return $queue;
    }

    /**
     * @brief Get exchange by name
     * @param $name string name of exchange
     * @return  object AMQPExchange
     */
    public function exchange($name, $type = AMQP_EX_TYPE_DIRECT)
    {
        return $this->declareExchange($name, $type);
    }

    /**
     * @brief Get queue by name
     * @param $name string name of exchange
     * @return  object AMQPQueue
     */
    public function queue($name)
    {
        return $this->declareQueue($name);
    }

    /**
     * Returns AMQPConnection instance
     *
     * @return AMQPConnection
     */
    public function getConnection()
    {
        return $this->_connection;
    }


    /**
     * Set AMQPChannel instance
     *
     * @return AMQPConnection
     */
    public function setChannel($channel)
    {
        $this->_channel = $channel;
        return $this->_connection;
    }

    /**
     * Returns AMQPChannel instance
     *
     * @return AMQPChannel
     */
    public function getChannel()
    {
        if ($this->_channel === null)
            $this->_channel = new AMQPChannel($this->_connection);
        return $this->_channel;
    }

}
