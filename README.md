# Railpage Core Code

[![Build Status](https://travis-ci.org/railpage/railpagecore.svg?branch=master)](https://travis-ci.org/railpage/railpagecore)

This repository contains the core Railpage PHP objects formerly located under their respective modules. 

Since Version 3.8 the core code has been progressively split from their UI modules and re-located into a PSR-4-compliant folder and file structure. 

The classes have been namespaced under the \Railpage\ namespace, for example: \Railpage\Locos\Locomotive. 

## Installing

If you're not using Composer, you should. We have a lot of dependencies and Composer will save you a lot of time. 

To start using the core code, execute `./composer.phar require railpage/railpage` to grab and install. 

## Using

Make sure you're using Composer. 

Assuming you've already included your Composer autoloader in your code, imort the desired modules by placing a `use` operator at the top of your .php file. For example: `use Railpage\Locos\Locomotive` will then enable `$Loco = new Locomotive($id)` further down in your code. 
