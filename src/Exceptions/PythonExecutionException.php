<?php
namespace App\Exceptions;

class PythonExecutionException extends \Exception {
    /**
     * Create a new Python execution exception instance.
     *
     * @param string $message The error message
     * @param int $code The error code (default: 0)
     * @param \Throwable|null $previous The previous exception
     */
    public function __construct(string $message = "", int $code = 0, \Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the string representation of the exception.
     *
     * @return string
     */
    public function __toString(): string {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
} 