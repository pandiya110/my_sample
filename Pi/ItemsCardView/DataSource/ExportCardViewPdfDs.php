<?php

namespace CodePi\ItemsCardView\DataSource;

use CodePi\Base\Libraries\DefaultIniSettings;
use CodePi\ItemsCardView\Commands\GetItemsCardView;
use CodePi\Base\Exceptions\DataValidationException;
use Illuminate\Support\MessageBag;
use CodePi\Base\Commands\CommandFactory;
use CodePi\Base\Libraries\PiLib;
use Mpdf\Mpdf;
use PDF;
use ZipArchive;
use URL;
/**
 * Class : ItemsCardViewDs
 * This class will handle the Card View List and Search , MultiSorting
 */
class ExportCardViewPdfDs {
    
    function exportCardViewPdf($command) {
        DefaultIniSettings::apply();
        $download_link = null;
        try {
            $params = $command->dataToArray();
            $params['event_id'] = PiLib::piEncrypt($params['event_id']);

            $objCommand = new GetItemsCardView($params);
            $grdResponse = CommandFactory::getCommand($objCommand);
            $html = '';
            $tr = URL::to('/') . '/resources/views/smart-app/assets/no-img.png';

            if (isset($grdResponse['items']) && !empty($grdResponse)) {
                //$html.= '<span>page count '.$pageItemsCount.'</span>';
                foreach ($grdResponse['items'] as $pageArray) {

                    $groupFalg = $groupFlagVal = '';
                    if (isset($params['column'][0]['column'])) {
                        $groupFalg = $params['column'][0]['name'];
                        $groupFlagVal = isset($pageArray['values'][0][$params['column'][0]['column']]) ? $pageArray['values'][0][$params['column'][0]['column']] : '';
                    }

                    $totalcount = count($pageArray['values']);
                    $checkrowcount = count($pageArray['values']) / 4;
                    $itemsCount = $checkrowcount == (int) $checkrowcount ? $checkrowcount : (int) $checkrowcount + 1;
                    $html.='
                            <div style="font-family: Roboto,sans-serif;">
                                    <table style="width: 100%">
                                        <tr>
                                            <td > <span><p>' . $groupFalg . ' : ' . $groupFlagVal . ' </p></span></td>
                                        </tr>
                                        <tr>
                                            <td>';

                    for ($i = 0; $i <= $itemsCount; $i++) {
                        $index = 0;
                        $item = $pageArray['values'][$index];


                        $html.='<table style="width: 100%;" cellspacing="10">
                                        <tr>';


                        for ($j = 0; $j <= 3; $j++) {
                            $secondCol_Name = $params['column'][1]['name'];
                            $index = (int) ($i * 4) + $j;
                            if ($index < $totalcount) {
                                $item = $pageArray['values'][$index];
                                $secondCol_Value = isset($item[$params['column'][1]['column']]) ? $item[$params['column'][1]['column']] : '';
                                $img = isset($item['original_image']) && $item['original_image'] ? $item['original_image'] : URL::to('/') . '/resources/views/smart-app/assets/no-img.png';
                                $img = $img . '?odnHeight=60&odnWidth=60&odnBg=ffffff';
                                $html.='<td style="width:25%">
                                             <table style="box-shadow: 0 2px 1px -1px rgba(0,0,0,.2), 0 1px 1px 0 rgba(0,0,0,.14), 0 1px 3px 0 rgba(0,0,0,.12);
         ">
                                                 <tr>
                                                     <td>
                                                         <img style="width: 80px;height: 80px;"  src="' . $img . '">
                                                     </td>
                                                     <td style="font-size: 12px;text-transform: capitalize;font-weight: 500;">
                                                     <div style="font-size: 14px;width: 150px;text-overflow: ellipsis;text-transform: capitalize;font-weight: 700;color: #565656;white-space: nowrap;overflow: hidden;">' . $item['signing_description'] . '</div>';
                                foreach ($grdResponse['itemsColumns'] as $columArray) { //Set Dynamic fields
                                    //$html.='<span>' . $columArray['name'] . ' : ' . $columArray['value'] . '</span><br/>';
                                    $html.='<div style="max-width: 150px;white-space: nowrap;overflow: hidden;text-overflow: ellipsis;"><span style="color: #B6B6B6">' . $columArray['lable'] . ':</span><span style="color: #635F5F">' . $item[$columArray['key']] . '</span></div>';
                                }




                                $html.= '</td>
                                                 </tr>
                                                 <tr style="font-size: 12px;text-transform: capitalize;font-weight: 500;">
                                                     <td>
                                                         <div>
                                                             <span style="color: #B6B6B6">' . $secondCol_Name . ':</span>
                                                             <span style="color: #635F5F"> ' . $secondCol_Value . '	</span>
                                                         </div>
                                                     </td>
                                                     <td>
                                                         
                                                     </td>
                                                 </tr>
                                             </table>
                                             
                                         </td>
                                         ';
                            } else {
                                $html.='<td></td>';
                            }
                        };

                        $html.= '</tr>
                                        </table>';
                    }
                    $html.='</td>
                        </tr>
        
                    </table>
                </div>';
                    /* foreach ($pageArray['values'] as $itemArray) { //Set Default Headers for Gird   


                      $html.= '<span> item count'.count($pageArray['values']).'--'.$index.$pageArray['values'][$index]['searched_item_nbr'].'</span>';
                      $html.='<div style="border: 1px solid #000;">
                      <span><p>' . $itemArray['signing_description'] . '</p></span>
                      ';

                      foreach ($itemArray['columnValues'] as $columArray) { //Set Dynamic fields
                      $html.='<span>' . $columArray['name'] . ' : ' . $columArray['value'] . '</span><br/>';
                      }

                      $html.='</div>';
                      $index++;
                      }
                      $html.='</div>
                      </div>'; */
                }

                $pdfFileName = 'Listbuilder_' . md5(time());
                $filePath = storage_path('app/public') . '/Export/export_items_to_pdf/';
                $filename = $filePath . $pdfFileName . '.pdf';
                // $mpdf = new \Mpdf\Mpdf([
                //     'margin_left' => 20,
                //     'margin_right' => 15,
                //     'margin_top' => 48,
                //     'margin_bottom' => 25,
                //     'margin_header' => 10,
                //     'margin_footer' => 10,
                //     //'showImageErrors' => true,
                //     'tempDir' => storage_path('/app/public/temp')
                // ]);
                // $mpdf->SetTitle('Listbuilder');
                // $mpdf->useOnlyCoreFonts = true;    // false is default
                // $mpdf->useSubstitutions = false;
                // $mpdf->simpleTables = true;
                // $mpdf->packTableData = true;
                // $pdftype = 'F'; //store file in our server  
                // $mpdf->WriteHTML($html);
                // $mpdf->Output($filename, $pdftype);
                //print_r($html);die;

                return ['html' => $html];
                $pdf = PDF::loadView('cardview', $grdResponse);
                // If you want to store the generated pdf to the server then you can use the store function
                $pdf->save(storage_path('app') . '/public/Export/export_items_to_pdf/' . '_filename.pdf');
                @chmod($filename, 0777);
                if (file_exists($filename)) {
                    $zipFolderName = $pdfFileName . '.zip';
                    $zip = new ZipArchive();
                    $source = storage_path('app') . '/public/Export/export_items_to_pdf/' . $zipFolderName;
                    $zip->open($source, ZipArchive::CREATE);
                    $downloadFile = file_get_contents($filename);
                    $zip->addFromString($pdfFileName . '.pdf', $downloadFile);
                    /**
                     * Folder permissions
                     */
                    if (file_exists($zipFolderName)) {
                        chmod($zipFolderName, 0777);
                    }
                    /**
                     * Unlink pdf file
                     */
                    if (file_exists($filename)) {
                        unlink($filename);
                    }
                    $zip->close();
                    $download_link = URL::to('stoarge/app/public/Export/export_items_to_pdf/' . $zipFolderName);
                }
            }
        } catch (\Exception $ex) {
            throw new DataValidationException($ex->getMessage() . $ex->getFile() . $ex->getLine(), new MessageBag());
        }

        return ['file_name' => $download_link];
    }

}
