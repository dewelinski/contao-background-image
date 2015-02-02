<?php


namespace postYou;


class BackgroundImageModel extends \Contao\Frontend
{
    function parseFrontendTemplateHook($strBuffer, $tmpName)
    {

        if ($tmpName == "mod_article") {
            if (preg_match_all("/id=\"(.*)\"/", $strBuffer, $res)) {
                if (!empty($res)) {
                    $alias = $res[1];

                    $dbRes = $this->Database->prepare("SELECT id,addBackgroundImage,backgroundImageFilepath FROM tl_article WHERE alias=?")
                        ->execute($alias);
                    $dbRes = $dbRes->fetchAssoc();
                    if ($dbRes["addBackgroundImage"] == 1 && isset($dbRes["backgroundImageFilepath"])) {
                        $objFile = \FilesModel::findByPk($dbRes["backgroundImageFilepath"]);
                        if ($objFile !== null && is_file(TL_ROOT . '/' . $objFile->path)) {
                            $strBuffer=$this->insertSytleTag($objFile->path,$strBuffer);
                        }
                    }
                }
            }
        }
        return $strBuffer;
    }

    function getContentElementHook($objElement, $strBuffer)
    {

        if ($objElement->row()["addBackgroundImage"] == '1' && isset($objElement->row()["backgroundImageFilepath"])) {
            $objFile = \FilesModel::findByPk($objElement->row()["backgroundImageFilepath"]);
            if ($objFile !== null && is_file(TL_ROOT . '/' . $objFile->path)) {
                $strBuffer=$this->insertSytleTag($objFile->path,$strBuffer);
            }

        }
        return $strBuffer;

    }

    function insertSytleTag($sourceFilePath,$strBuffer){
        if($GLOBALS['TL_CONFIG']['mobileBackgroundImage']===true)
            $resolutions=(unserialize($GLOBALS['TL_CONFIG']['imageList']));
        $path_parts = pathinfo($sourceFilePath);
        $rID=uniqid($path_parts['filename']."_");

        $strBuffer = preg_replace("/class=\"/", "class=\" " .$rID." ", $strBuffer, 1);
        $tmpBuffer= "<style scoped='scoped' type='text/css'>\n.".$rID." {background-image:url(".$sourceFilePath.")}";

        if(isset($resolutions) && is_array($resolutions) && !empty($resolutions)){ // mobile Versions are enabled and there is a list
            foreach($resolutions as $value) {

                $objFile = new \File($path_parts['dirname'] . "/" . $path_parts['filename'] . $value[2] . "." . $path_parts['extension'],true);

                if ($objFile->exists()){ // file exists
                    $tmpBuffer .= "\n@media screen and (";
                    if ($value[0] == 1) {
                        $tmpBuffer .= "min";
                    } else {
                        $tmpBuffer .= "max";
                    }
                    $tmpBuffer .= "-width: " . $value[1] . "px){
                                    .".$rID." {background-image:url(" . $path_parts['dirname'] . "/" . $path_parts['filename'] . $value[2] . "." . $path_parts['extension'] . ")}
                                }";
                }
            }

        }
        $tmpBuffer.='</style>';
        //return $tmpBuffer.$strBuffer;
        $strBuffer =preg_replace("/>/",">\n".$tmpBuffer,$strBuffer,1);
        return $strBuffer;
    }
}