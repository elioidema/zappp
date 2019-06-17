<?php

class CrudManager
{
    public static function registerRoutes($apiName)
	{
        // generic CRUD services

        // (C)reate > POST   /table
        // (R)ead   > GET    /table[/id]
        // (R)ead   > GET    /table[/column/content]
        // (U)pdate > PUT    /table/id
        // (D)elete > DELETE /table/id


        Flight::route('GET /crud/@table', function($table) {
            CrudManager::checkIfTableIsAllowed($table);
            $array = array();
            $all = CrudBeanHandler::findAllBeans($table);
            foreach ($all as $bean) 
            {
                $array[] = CrudBeanHandler::exportBean($bean);
            }
            
            Flight::json(HttpHandler::createResponse(200, $array));
        }
        );

        Flight::route('GET /crud/@table/@id', function($table, $id) {
            CrudManager::checkIfTableIsAllowed($table);
            $result = CrudBeanHandler::findBean($table, $id);
            $array = CrudBeanHandler::exportBean($result);
            Flight::json(HttpHandler::createResponse(200, $array));
        }
        );

        Flight::route('POST /crud/@table', function($table) {
            CrudManager::checkIfTableIsAllowed($table);
            $posted = HttpHandler::handleRequest();
            $bean = CrudBeanHandler::dispenseBean($table);
            CrudBeanHandler::updateBean($bean, $posted);
            CrudBeanHandler::storeBean($bean);
            $array = CrudBeanHandler::exportBean($bean);
            Flight::json(HttpHandler::createResponse(201, $array));
        }
        );

        Flight::route('POST /crud/@table/@id', function($table, $id) {
            CrudManager::checkIfTableIsAllowed($table);

            $posted = HttpHandler::handleRequest();
            
            $bean = CrudBeanHandler::findBean($table, $id);

            // fb($posted);
            
            CrudBeanHandler::updateBean($bean, $posted);
            
            CrudBeanHandler::storeBean($bean);
            
            // 	print_r($bean);
            
            $array = CrudBeanHandler::exportBean($bean);
            
            // 	print_r($array);
            
            Flight::json(HttpHandler::createResponse(200, $array));
        }
        );

        Flight::route('DELETE /crud/@table/@id', function($table, $id) {
            CrudManager::checkIfTableIsAllowed($table);
            $bean = CrudBeanHandler::findBean($table, $id);
            
            CrudBeanHandler::trashBean($bean);
            
            Flight::json(HttpHandler::createResponse(200, null));
        }
        );

    }

    private static function checkIfTableIsAllowed($table)
    {
        if (!(
            // $table == 'contentitem' ||
            $table == 'image' ||
            // $table == 'object' ||
            //   $table == 'party' ||
            //   $table == 'project' ||
            $table == 'questioninstance'
            //   $table == 'questions' ||
            //   $table == 'settings'
        ))
        {
            throw new Exception('Illegal CRUD operation.');
        }
    }
}