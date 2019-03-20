<?php

namespace CodePi\Base\Commands;

use CodePi\Base\Commands\iCommands,
    CodePi\Base\Exceptions\CommandException,
    CodePi\Base\DataTransformers\DataSourceResponse;

class CommandFactory {

    static function getCommand($objCommand, $auto = false) {
        $objCommand->isAuto = $auto;        
        $cmdName = str_replace('Commands', 'Executables', get_class($objCommand));        
        if (!class_exists($cmdName)) {
            $message = 'Command ' . $cmdName . ' does not exisit.';

            throw new CommandException($message);
        }
        $validationName = str_replace('Commands', 'Validations', get_class($objCommand));
        //$objValidationName=$validationName."Validation";
        if (class_exists($validationName)) {
            //$objCommand->isAuto = $auto;
            $test = $objCommand->dataToArray();
            //dd($objCommand);
            if (!\App::make($validationName)->validate($test)) {
                //throw new CommandException ( 'Command validation failed' );
            }
        }

        return \App::make($cmdName)->execute($objCommand);
    }
    
}
