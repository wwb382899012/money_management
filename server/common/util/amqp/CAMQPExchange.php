<?php

/**
 * @ingroup CAMQP
 */

/**
 * @class   CAMQPExchange
 * @brief   Represents an AMQP exchange.
 * @details
 *  
 * "A" - Team:
 * @author     Andrey Evsyukov <thaheless@gmail.com>
 * @author     Alexey Spiridonov <a.spiridonov@2gis.ru>
 * @author     Alexey Papulovskiy <a.papulovskiyv@2gis.ru>
 * @author     Alexander Biryukov <a.biryukov@2gis.ru>
 * @author     Alexander Radionov <alex.radionov@gmail.com>
 * @author     Andrey Trofimenko <a.trofimenko@2gis.ru>
 * @author     Artem Kudzev <a.kiudzev@2gis.ru>
 *   
 * @link       http://www.2gis.ru
 * @copyright  2GIS
 * @license http://www.Modframework.com/license/
 *
 * Requirements:
 * ---------------------
 *  - Mod 1.1.x or above
 *  - AMQP PHP library
 *
 */
class CAMQPExchange extends AMQPExchange
{
	 /**
     * Publish a message to an exchange.
     *
     * Publish a message to the exchange represented by the AMQPExchange object.
     *
     * @param string  $message     The message to publish.
     * @param string  $routing_key The optional routing key to which to
     *                             publish to.
     * @param integer $flags       One or more of AMQP_MANDATORY and
     *                             AMQP_IMMEDIATE.
     * @param array   $attributes  One of content_type, content_encoding,
     *                             message_id, user_id, app_id, delivery_mode,
     *                             priority, timestamp, expiration, type
     *                             or reply_to, headers.
     *
     * @throws AMQPExchangeException   On failure.
     * @throws AMQPChannelException    If the channel is not open.
     * @throws AMQPConnectionException If the connection to the broker was lost.
     *
     * @return boolean TRUE on success or FALSE on failure.
     */
    public function  publish($message, $routingKey=null, $flags = AMQP_NOPARAM, array $attributes = array())
    {
        return parent::publish($message, $routingKey, $flags, $attributes );
    }

 
    /**
     * Delete the exchange from the broker.
     *
     * @param string  $exchangeName Optional name of exchange to delete.
     * @param integer $flags        Optionally AMQP_IFUNUSED can be specified
     *                              to indicate the exchange should not be
     *                              deleted until no clients are connected to
     *                              it.
     *
     * @throws AMQPExchangeException   On failure.
     * @throws AMQPChannelException    If the channel is not open.
     * @throws AMQPConnectionException If the connection to the broker was lost.
     *
     * @return boolean true on success or false on failure.
     */
    public function delete($exchangeName = null, $flags = AMQP_NOPARAM)
    {
        return parent::delete($exchangeName,$flags);
    }
    
}