<?php
/**
 * Description of Admin
 *
 * @author enterpi
 */

namespace CodePi\Base\Libraries\Transfer;

interface iTransfer {
    function setContainer($container);
    function getContainer();
    function upload();
}
