<?php

namespace Drupal\viidia_zoho_creator\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DrupalDateTime;
use Symfony\Component\HttpFoundation\JsonResponse;

class CuratorsController extends ControllerBase
{
    public function import() {
        $importedUsers = [];

        $userStorage = \Drupal::service('entity_type.manager')->getStorage('user');
        $ids = $userStorage->getQuery()->condition('status', 1)->condition('roles', 'curator')->execute();
        $users = $userStorage->loadMultiple($ids);

        $usersByKeys = [];
        foreach ($users as $user) {
            $usersByKeys[$user->getUsername()] = $user;
        }

        if (!empty($usersByKeys)){
            $config = \Drupal::config('viidia_zoho_creator.settings');
            $settings = $config->get();

            $zohoXml = new \SimpleXMLElement('<ZohoCreator></ZohoCreator>');
            $zohoXml->addChild('applicationlist');
            $zohoXml->applicationlist->addChild('application');
            $zohoXml->applicationlist->application->addAttribute('name', $settings['viidia_zoho_creator_application_name']);
            $zohoXml->applicationlist->application->addChild('formlist');
            $zohoXml->applicationlist->application->formlist->addChild('form');
            $zohoXml->applicationlist->application->formlist->form->addAttribute('name', $settings['viidia_zoho_creator_curators_form_name']);

            foreach ($usersByKeys as $user) {
                $addXmlEle = $zohoXml->applicationlist->application->formlist->form->addChild('add');

                $field = $addXmlEle->addChild('field');
                $field->addAttribute('name', 'Username');
                $field->addChild('value', $user->getUsername());

                $field = $addXmlEle->addChild('field');
                $field->addAttribute('name', 'Display_Name');
                $field->addChild('value', $user->field_name->value);

                $field = $addXmlEle->addChild('field');
                $field->addAttribute('name', 'Email');
                $field->addChild('value', $user->getEmail());

                $createdTime = $user->getCreatedTime();
                if (!empty($createdTime)) {
                    $field = $addXmlEle->addChild('field');
                    $field->addAttribute('name', 'Registered_Date');
                    $field->addChild('value', DrupalDateTime::createFromTimestamp($createdTime)->format('d-M-Y H:i:s'));
                }

                $lastAccessedTime = $user->getLastAccessedTime();
                if (!empty($lastAccessedTime)) {
                    $field = $addXmlEle->addChild('field');
                    $field->addAttribute('name', 'Last_Accessed_Date');
                    $field->addChild('value', DrupalDateTime::createFromTimestamp($lastAccessedTime)->format('d-M-Y H:i:s'));
                }

                $field = $addXmlEle->addChild('field');
                $field->addAttribute('name', 'User_ID');
                $field->addChild('value', $user->id());
            }

            $xmlStr = $zohoXml->asXML();

            $client = \Drupal::httpClient();
            $response = $client->request('POST', 'https://creator.zoho.com/api/xml/write', [
                'form_params' => [
                    'authtoken' => $settings['viidia_zoho_creator_auth_token'],
                    'scope' => 'creatorapi',
                    'XMLString' => $xmlStr,
                    'zc_ownername' => $settings['viidia_zoho_creator_zoho_creator']
                ]
            ]);

            if ($response->getStatusCode() == 200) {
                $body = $response->getBody();
                $responseXml = simplexml_load_string($body->__toString());
                foreach ($responseXml->result as $result) {
                    if (!empty($result->form->add->status) && $result->form->add->status->__toString() === 'Success') {
                        $username = $result->form->add->values->field[0]->value->__toString();
                        $displayName = $result->form->add->values->field[1]->value->__toString();
                        $zohoCreatorRecordID = $result->form->add->values->field[6]->value->__toString();
                        array_push($importedUsers, [
                            'username' => $username,
                            'displayName' => $displayName,
                            'zohoCreatorRecordID' => $zohoCreatorRecordID
                        ]);

                        if (isset($usersByKeys[$username])) {
                            $usersByKeys[$username]->field_zoho_creator_record_id->setValue($zohoCreatorRecordID);
                            $usersByKeys[$username]->save();
                        }
                    } elseif (!empty($result->form->add->status) && $result->form->add->status->__toString() !== 'Success') {
                        if (isset($usersByKeys[$username])) {
                            $usersByKeys[$username]->field_zoho_creator_record_id->setValue('');
                            $usersByKeys[$username]->save();
                        }
                    }
                }
            }
        }

        return new JsonResponse($importedUsers, 200);
    }

    public function export() {
        $columns = array(
            'A' => 'Username',
            'B' => 'Display Name',
            'C' => 'Email',
            'D' => 'Registered Date',
            'E' => 'Last Accessed Date',
            'F' => 'Zoho Creator Record ID',
            'G' => 'User ID'
        );

        $objPhpExcel = new \PHPExcel();
        $worksheet = $objPhpExcel->setActiveSheetIndex(0);

        foreach ($columns as $colKey => $col) {
            $cell = $colKey . '1';
            $worksheet->setCellValue($cell, $col);
        }

        $userStorage = \Drupal::service('entity_type.manager')->getStorage('user');
        $ids = $userStorage->getQuery()->condition('status', 1)->condition('roles', 'curator')->execute();
        $users = $userStorage->loadMultiple($ids);

        $row = 1;
        foreach ($users as $user) {
            $createdTime = $user->getCreatedTime();
            $lastAccessedTime = $user->getLastAccessedTime();

            $row++;
            $worksheet->setCellValue('A' . $row, $user->getUsername());
            $worksheet->setCellValue('B' . $row, $user->field_name->value);
            $worksheet->setCellValue('C' . $row, $user->getEmail());
            $worksheet->setCellValue('D' . $row,
                $createdTime ? DrupalDateTime::createFromTimestamp($createdTime)->format('d-M-Y H:i:s') : '');
            $worksheet->setCellValue('E' . $row,
                $lastAccessedTime ? DrupalDateTime::createFromTimestamp($lastAccessedTime)->format('d-M-Y H:i:s') : '');
            $worksheet->setCellValue('F' . $row, $user->field_zoho_creator_record_id->value);
            $worksheet->setCellValue('G' . $row, $user->id());
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="curators.xlsx"');
        header('Cache-Control: max-age=1');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
        header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header('Pragma: public'); // HTTP/1.0

        $objWriter = \PHPExcel_IOFactory::createWriter($objPhpExcel, 'Excel2007');
        $objWriter->save('php://output');

        exit;
    }
}