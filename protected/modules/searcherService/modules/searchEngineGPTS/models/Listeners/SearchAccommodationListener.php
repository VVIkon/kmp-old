<?php

/**
 * Обрабочик ответа /searchAccommodation API GPTS для разбора офферов отелей
 */
class SearchAccommodationListener implements JsonStreamingParser\Listener
{
    protected $json;

    protected $stack;
    protected $key;

    protected $level;

    /** @var Callable */
    protected $callback;

    /** @var bool Флаг, сигнализирующий начало разбора коллекции офферов */
    protected $offersStarted;

    /** @param Callable $callback */
    public function __construct($callback = null)
    {
        $this->callback = $callback;
        $this->offersStarted = false;
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
        $this->stack[] = [];
        // Reset the stack when entering the second level
        if ($this->level == 2) {
            $this->stack = [];
            $this->key[$this->level] = null;
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
        // Call the callback when returning to the second level
        if ($this->offersStarted) {
          if ($this->level == 2 && is_callable($this->callback)) {
            call_user_func($this->callback, $this->json);
          } else if ($this->level == 1) {
            $this->offersStarted = false;
          }
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

        /** hotel offers collection */
        if ($this->key[$this->level] == 'hotelOffers') {
            $this->offersStarted = true;
        }
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
