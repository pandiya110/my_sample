<?php

namespace CodePi\Base\Http;

use App\Http\Controllers\Controller;
use CodePi\Base\Commands\CommandFactory;
use CodePi\Base\DataTransformers\DataSourceResponse;
use CodePi\Base\Exceptions\DataValidationException;
use CodePi\Base\Commands\BaseCommand;
use CodePi\ImportExportLog\Commands\ErrorLog;
use Throwable;

class PiController extends Controller {

    protected function run(BaseCommand $command, $successMsg = '', $failureMsg = '') {
        try {

            $result = CommandFactory::getCommand($command, TRUE);
            $arrResponse = new DataSourceResponse($result, $successMsg, TRUE);
        } catch (Throwable $ex) {

            $exMsg = 'Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();
            $arrResponse = new DataSourceResponse($ex->getMessage(), $ex->getMessage(), FALSE, 401);
        } catch (DataValidationException $ex) {

            $exMsg = 'Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();
            $arrResponse = new DataSourceResponse($ex->getMessage(), $ex->getMessage(), FALSE, 401);
        } catch (\Exception $ex) {

            $exMsg = 'Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();
            $arrResponse = new DataSourceResponse($exMsg, $failureMsg, FALSE, 500);
            CommandFactory::getCommand(new ErrorLog(array('message' => $exMsg)), TRUE);
        } finally {
            return response()->json($arrResponse->formatMessage());
        }
    }

}
