<?php

/**
 * @ingroup CAMQP
 */

/**
 * @class   CAMQPQueue
 * @brief   Represents an AMQP queue
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
class CAMQPQueue extends AMQPQueue
{
	/**
     * @param integer $flags A bitmask of any of the flags: AMQP_NOACK. 
	 * @brief Retrieve the next message from the queue.
	 */
    public function get($flags = AMQP_NOPARAM ) //As original AMQPQueue default value
    {
	   return parent::get($flags);
    }
    
    /**
     * Acknowledge the receipt of a message.
     *
     * This method allows the acknowledgement of a message that is retrieved
     * without the AMQP_AUTOACK flag through AMQPQueue::get() or
     * AMQPQueue::consume()
     *
     * @param string  $delivery_tag The message delivery tag of which to
     *                              acknowledge receipt.
     * @param integer $flags        The only valid flag that can be passed is
     *                              AMQP_MULTIPLE.
     *
     * @throws AMQPChannelException    If the channel is not open.
     * @throws AMQPConnectionException If the connection to the broker was lost.
     *
     * @return boolean
     */
    public function ack($deliveryTag, $flags = AMQP_NOPARAM)
    {
        return parent::ack($deliveryTag, $flags);
    }

    /**
     * Cancel a queue that is already bound to an exchange and routing key.
     *
     * @param string $consumerTag  The consumer tag cancel. If no tag provided,
     *                             or it is empty string, the latest consumer
     *                             tag on this queue will be used and after
     *                             successful request it will set to null.
     *                             If it also empty, no `basic.cancel`
     *                             request will be sent. When consumer_tag give
     *                             and it equals to latest consumer_tag on queue,
     *                             it will be interpreted as latest consumer_tag usage.
     *
     * @throws AMQPChannelException    If the channel is not open.
     * @throws AMQPConnectionException If the connection to the broker was lost.
     *
     * @return bool;
     */
    public function cancel($consumerTag = '')
    {
        return parent::cancel($consumerTag);
    }

    /**
     * Delete a queue from the broker.
     *
     * This includes its entire contents of unread or unacknowledged messages.
     *
     * @param integer $flags        Optionally AMQP_IFUNUSED can be specified
     *                              to indicate the queue should not be
     *                              deleted until no clients are connected to
     *                              it.
     *
     * @throws AMQPChannelException    If the channel is not open.
     * @throws AMQPConnectionException If the connection to the broker was lost.
     *
     * @return integer The number of deleted messages.
     */
    public function delete($flags = AMQP_NOPARAM)
    {
        return parent::delete($flags);
    }

     /**
     * Purge the contents of a queue.
     *
     * @throws AMQPChannelException    If the channel is not open.
     * @throws AMQPConnectionException If the connection to the broker was lost.
     *
     * @return boolean
     */
    public function purge()
    {
        return parent::purge();
    }

    /**
     * Get the configured name.
     *
     * @return string The configured name as a string.
     */
    public function getName()
    {
        return parent::getName();
    }
    /**
     * Mark a message as explicitly not acknowledged.
     *
     * Mark the message identified by delivery_tag as explicitly not
     * acknowledged. This method can only be called on messages that have not
     * yet been acknowledged, meaning that messages retrieved with by
     * AMQPQueue::consume() and AMQPQueue::get() and using the AMQP_AUTOACK
     * flag are not eligible. When called, the broker will immediately put the
     * message back onto the queue, instead of waiting until the connection is
     * closed. This method is only supported by the RabbitMQ broker. The
     * behavior of calling this method while connected to any other broker is
     * undefined.
     *
     * @param string  $delivery_tag Delivery tag of last message to reject.
     * @param integer $flags        AMQP_REQUEUE to requeue the message(s),
     *                              AMQP_MULTIPLE to nack all previous
     *                              unacked messages as well.
     *
     * @throws AMQPChannelException    If the channel is not open.
     * @throws AMQPConnectionException If the connection to the broker was lost.
     *
     * @return boolean
     */
    public function nack($delivery_tag, $flags = AMQP_NOPARAM)
    {
        return parent::nack($delivery_tag, $flags);
    }
    /**
     * Mark one message as explicitly not acknowledged.
     *
     * Mark the message identified by delivery_tag as explicitly not
     * acknowledged. This method can only be called on messages that have not
     * yet been acknowledged, meaning that messages retrieved with by
     * AMQPQueue::consume() and AMQPQueue::get() and using the AMQP_AUTOACK
     * flag are not eligible.
     *
     * @param string  $delivery_tag Delivery tag of the message to reject.
     * @param integer $flags        AMQP_REQUEUE to requeue the message(s).
     *
     * @throws AMQPChannelException    If the channel is not open.
     * @throws AMQPConnectionException If the connection to the broker was lost.
     *
     * @return boolean
     */
    public function reject($delivery_tag, $flags = AMQP_NOPARAM)
    {
        return parent::reject($delivery_tag, $flags);
    }

}
