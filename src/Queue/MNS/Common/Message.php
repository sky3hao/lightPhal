<?php
/**
 * Filename: Message.php.
 * User: George
 * Date: 2018/5/7
 * Time: 下午1:27
 */

namespace Tengyue\Infra\Queue\MNS\Common;

class Message
{

    protected $_handlerName = null;

    protected $_messageBody = [];

    /**
     * @param string $serialize
     * @return $this
     */
    public function parseBind(string $serialize)
    {
        $messageBody = unserialize($serialize);

        isset($messageBody['handlerName']) && $this->setHandlerName($messageBody['handlerName']);

        isset($messageBody['messageBody']) && $this->setMessageBody($messageBody['messageBody']);

        return $this;
    }

    public function getSerializeBody()
    {
        $messageBody = [];

        !empty($this->getHandlerName()) && ($messageBody['handlerName'] = $this->getHandlerName());

        !empty($this->getMessageBody()) && ($messageBody['messageBody'] = $this->getMessageBody());

        return serialize($messageBody);
    }

    /**
     * @param $handlerName
     * @return $this
     */
    public function setHandlerName($handlerName)
    {
        $this->_handlerName = $handlerName;

        return $this;
    }

    /**
     * @param $messageBody
     * @return $this
     */
    public function setMessageBody($messageBody)
    {
        $this->_messageBody = $messageBody;

        return $this;
    }

    /**
     * @return null
     */
    public function getHandlerName()
    {
        return $this->_handlerName;
    }

    /**
     * @return array
     */
    public function getMessageBody()
    {
        return $this->_messageBody;
    }

}