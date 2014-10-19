Error - A simple, stackable, closure based error handler
=====

[![Build Status](https://travis-ci.org/joegreen0991/error.svg)](https://travis-ci.org/joegreen0991/error)
[![Coverage Status](https://img.shields.io/coveralls/joegreen0991/error.svg)](https://coveralls.io/r/joegreen0991/error)


This library makes it easier to handle exceptions of different types in different ways.

This library automatically converts errors triggered within your code to an instance of `ErrorException` which is thrown.

Installation
------------
Install via composer

```
{
    "require": {
        "joegreen0991/error": "1.*"
    }
}

```

Usage
-----

Simply instantiate the class and it will automatically apply an error handler to convert errors to exceptions, using `set_error_handler`.

It will also create an exception handler using `set_exception_handler` which checks for closures handling the type of exception that has been thrown.

~~~PHP

$handler = new Error\Handler();

$handler->error(function(HttpRouteNotFoundError $e)
{
    echo "Route does not exist";

})->error(function(PDOException $e)
{
    echo "Database is down!";

})->error(function(ErrorException $e)
{
    echo "A notice level error has occurred";

})->error(function(Exception $e)
{
    echo "Whoops - Something has gone terribly wrong";
});

~~~
