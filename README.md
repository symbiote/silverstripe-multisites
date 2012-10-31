# SilverStripe Multisites

## Overview

Allows for multiple websites to be managed through a single site tree. 

This is an alternative module to the Subsites module; it avoids any session
tracking of the 'current' website, and doesn't perform any query modification 
at runtime to change the 'site' context of queries you execute

## Installation

* Add the module and the multivaluefield module
* Run dev/build
* Run dev/tasks/MultisitesInitTask

## Setting up sites (and additional sites)

* In the backend go to the Pages section and click on the Website 
* Enter in the full path to the site in the 'Host' field, without the http:// 
  lead - eg mysitedomain.com, or localhost/sub/folder for a development site
* Hit save
* To add a new site, click the Pages section; you should have an 'Add site' 
  button
* Enter details about the new site, the Host field being the most important

## Known issues

* See github
