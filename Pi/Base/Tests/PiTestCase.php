<?php

namespace CodePi\Base\Tests;

use TestCase;
use CodePi\Base\Commands\CommandFactory;
use CodePi\Base\Exceptions\DataValidationException;

class PiTestCase extends TestCase {

    public function execute($command) {
        try{
            $response = CommandFactory::getCommand($command, true);
        }catch (DataValidationException $ex) {
            $response = $ex->getMessage();
        }  catch (\Exception $e) {
            $response = $e->getMessage();
        } finally {
            return $response;
        }
    }

}
