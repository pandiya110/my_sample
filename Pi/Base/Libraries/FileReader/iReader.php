<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Admin
 *
 * @author enterpi
 */

namespace CodePi\Base\Libraries\FileReader;

interface iReader {

    function getData();

    function setHeaders($headers);

    function getHeaders();

    function validation();
}
