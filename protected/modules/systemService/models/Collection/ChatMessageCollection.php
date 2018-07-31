<?php

/**
 * Коллекция сообщений чата
 */
class ChatMessageCollection implements Countable
{
    /**
     * @var SplObjectStorage
     */
    protected $messages;

    /**
     * ChatMessageCollection constructor.
     * @param array $inpMessages
     */
    public function __construct(array $inpMessages)
    {
        $this->messages = new \SplObjectStorage;

        if (count($inpMessages)) {
            foreach ($inpMessages as $message) {
                $this->messages->attach($message);
            }
        }
    }

    /**
     * Подтверждение отправки сообщений
     */
    public function confirmSending()
    {
        if (count($this->messages)) {
            foreach ($this->messages as $message) {
                $message->confirmSending();
                $message->save();
            }
        }
    }

    /**
     *
     * @return array
     */
    public function toArray()
    {
        $msgArr = [];

        if (count($this->messages)) {
            foreach ($this->messages as $message) {
                $msgArr[] = $message->toArray();
            }
        }

        return $msgArr;
    }

    /**
     * @return mixed
     */
    public function count()
    {
        return count($this->messages);
    }
}