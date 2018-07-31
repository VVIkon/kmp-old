<?php

/**
 * Обрабочик ответа команды УТК GetClientsList
 */
class GetClientsListListener implements JsonStreamingParser\Listener
{
    protected $json;

    protected $stack;
    protected $key;

    protected $level;

    /** @var Callable */
    protected $callback;
    /** @var Callable */
    protected $infocallback;
    /** @var Callable */
    protected $queuecallback;

    /**
    * @param Callable $infocallback коллбэк для обработки служебной информации запроса
    * @param Callable $queuecallback коллбэк для обработки данных о номере сообщения
    * @param Callable $callback коллбэк для обработки данных компаний
    */
    public function __construct($infocallback,$queuecallback,$callback = null)
    {
        $this->infocallback = $infocallback;
        $this->queuecallback = $queuecallback;
        $this->callback = $callback;
    }

    public function getJson()
    {
        return $this->json;
    }

    public function startDocument()
    {
        $this->stack = [];
        $this->level = 0;
        /* Key is an array so that we can remember keys per level to avoid
        it being reset when processing child keys. */
        $this->key = [];
    }

    public function endDocument()
    {
        // w00t!
    }

    public function startObject()
    {
        $this->level++;

        if ($this->level == 2) {
            /** отдать служебную информацию запроса */
            $obj = array_pop($this->stack);
            $this->json = $obj;
            call_user_func($this->infocallback, $this->json);
            $this->stack = [];
            $this->key[$this->level] = null;
        } elseif ($this->level == 3) {
            /** отдать номер сообщения, если есть */
            $obj = array_pop($this->stack);
            if (!is_null($obj) && is_array($obj)) {
              $this->json = $obj;
              call_user_func($this->queuecallback, $this->json);
            }
            $this->stack = [];
            $this->key[$this->level] = null;
        } else {
            $this->stack[] = [];
        }
    }

    public function endObject()
    {
        $this->level--;
        $obj = array_pop($this->stack);
        if (empty($this->stack)) {
            // doc is DONE!
            $this->json = $obj;
        } else {
            $this->value($obj);
        }
        // Call the callback when returning to collection level
        if ($this->level == 3 && is_callable($this->callback)) {
            call_user_func($this->callback, $this->json);
        }
    }

    public function startArray()
    {
        $this->startObject();
    }

    public function endArray()
    {
        $this->endObject();
    }

    /**
     * @param string $key
     */
    public function key($key)
    {
        $this->key[$this->level] = $key;
    }

    /**
     * Value may be a string, integer, boolean, null
     * @param mixed $value
     */
    public function value($value)
    {
        $obj = array_pop($this->stack);
        if (!empty($this->key[$this->level])) {
            $obj[$this->key[$this->level]] = $value;
            $this->key[$this->level] = null;
        } else {
            $obj[] = $value;
        }
        $this->stack[] = $obj;
    }

    public function whitespace($whitespace)
    {
        // do nothing
    }
}
