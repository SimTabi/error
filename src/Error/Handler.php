<?php Error;

use Closure;
use ReflectionFunction;
use ErrorException;

class Handler {

    /**
     * All of the register exception handlers.
     *
     * @var array
     */
    protected $handlers = array();
    
    public function __construct()
    {
        $this->registerHandlers();
    }
    
    /**
     * Register the PHP error handler.
     *
     * @return void
     */
    protected function registerErrorHandler()
    {
        set_error_handler(array($this, 'handleError'));
    }

    /**
     * Register the PHP exception handler.
     *
     * @return void
     */
    protected function registerExceptionHandler()
    {
        set_exception_handler(array($this, 'handleException'));
    }

    /**
     * Register the PHP shutdown handler.
     *
     * @return void
     */
    protected function registerShutdownHandler()
    {
        register_shutdown_function(array($this, 'handleShutdown'));
    }

    /**
     * Handle a PHP error for the application.
     *
     * @param  int     $level
     * @param  string  $message
     * @param  string  $file
     * @param  int     $line
     * @param  array   $context
     */
    public function handleError($level, $message, $file, $line, $context)
    {
        if (error_reporting() & $level)
        {
            throw new ErrorException($message, $level, 0, $file, $line);
        }
    }

    /**
     * Handle an exception for the application.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function handleException($exception)
    {
        $response = $this->callCustomHandlers($exception);

        // If no response was sent by this custom exception handler, we will call the
        // default exception displayer for the current application context and let
        // it show the exception to the user / developer based on the situation.

        isset($response) or $response = $this->formatException($exception);


        $this->display($response);

        exit(1);
    }

    /**
     * Handle the PHP shutdown event.
     *
     * @return void
     */
    public function handleShutdown()
    {
        $error = error_get_last();

        // If an error has occurred that has not been displayed, we will create a fatal
        // error exception instance and pass it into the regular exception handling
        // code so it can be displayed back out to the developer for information.
        if (isset($error))
        {
            extract($error);

            if (!$this->isFatal($type))
                return;

            $this->handleException(new FatalErrorException($message, $type, 0, $file, $line));
        }
    }

    /**
     * Determine if the error type is fatal.
     *
     * @param  int   $type
     * @return bool
     */
    protected function isFatal($type)
    {
        return in_array($type, array(E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE));
    }

    /**
     * Handle the given exception.
     *
     * @param  Exception  $exception
     * @param  bool  $fromConsole
     * @return void
     */
    protected function callCustomHandlers($exception)
    {
        foreach ($this->handlers as $handler) {
            // If this exception handler does not handle the given exception, we will just
            // go the next one. A handler may type-hint an exception that it handles so
            //  we can have more granularity on the error handling for the developer.
            if (!$this->handlesException($handler, $exception))
            {
                continue;
            } else
            {
                $code = $exception->getCode();
            }

            // We will wrap this handler in a try / catch and avoid white screens of death
            // if any exceptions are thrown from a handler itself. This way we will get
            // at least some errors, and avoid errors with no data or not log writes.
            try {
                $response = $handler($exception, $code);
            } catch (\Exception $e) {
                $response = $this->formatException($e);
            }

            // If this handler returns a "non-null" response, we will return it so it will
            // get sent back to the browsers. Once the handler returns a valid response
            // we will cease iterating through them and calling these other handlers.
            if (isset($response))
            {
                return $response;
            }
        }
    }

    /**
     * Determine if the given handler handles this exception.
     *
     * @param  Closure    $handler
     * @param  Exception  $exception
     * @return bool
     */
    protected function handlesException(Closure $handler, $exception)
    {
        $reflection = new ReflectionFunction($handler);

        return $reflection->getNumberOfParameters() == 0 || $this->hints($reflection, $exception);
    }

    /**
     * Determine if the given handler type hints the exception.
     *
     * @param  ReflectionFunction  $reflection
     * @param  Exception  $exception
     * @return bool
     */
    protected function hints(ReflectionFunction $reflection, $exception)
    {
        $parameters = $reflection->getParameters();

        $expected = $parameters[0];

        return !$expected->getClass() or $expected->getClass()->isInstance($exception);
    }

    /**
     * Format an exception thrown by a handler.
     *
     * @param  Exception  $e
     * @return string
     */
    protected function formatException(\Exception $e)
    {
        $location = $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine();

        return 'Error in exception handler: ' . $location;
    }

    /**
     * 
     * @param type $message
     */
    protected function display($message)
    {
        echo PHP_EOL . $message . PHP_EOL . PHP_EOL;
    }

    /**
     * Register an application error handler.
     *
     * @param  Closure  $callback
     * @return void
     */
    public function error(Closure $callback)
    {
        array_unshift($this->handlers, $callback);
    }

    /**
     * Register an application error handler at the bottom of the stack.
     *
     * @param  Closure  $callback
     * @return void
     */
    public function pushError(Closure $callback)
    {
        $this->handlers[] = $callback;
    }

    /**
     * Register the exception / error handlers for the application.
     *
     * @param  string  $environment
     * @return void
     */
    public function registerHandlers()
    {
        $this->registerErrorHandler();

        $this->registerExceptionHandler();

        $this->registerShutdownHandler();
    }
    
    public function getHandlers()
    {
        return $this->handlers;
    }
    
}