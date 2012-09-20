<?php

/**
 * Filesharing Lib
 * @copyright   CONTREXX CMS - COMVATION AG
 * @author      Comvation Development Team <info@comvation.com>
 * @version     1.0.0
 * @package     contrexx
 * @subpackage  filesharing
 */
abstract class FilesharingLib
{
    /**
     * init the pl uploader which is directly included in the webpage
     *
     * @return integer the uploader id
     */
    protected function initUploader()
    {
        JS::activate('cx'); // the uploader needs the framework
        require_once ASCMS_CORE_MODULE_PATH . '/upload/share/uploadFactory.class.php';

        /**
         * Name of the upload instance
         */
        $uploaderInstanceName = 'exposed_combo_uploader';

        /**
         * jQuery selector of the HTML-element where the upload folder-widget shall be put in
         */
        $uploaderFolderWidgetContainer = '#uploadFormField_uploadWidget';

        // create an exposedCombo uploader
        $uploader = UploadFactory::getInstance()->newUploader('exposedCombo');

        //set instance name so we are able to catch the instance with js
        $uploader->setJsInstanceName($uploaderInstanceName);

        // specifies the function to call when upload is finished. must be a static function
        $uploader->setFinishedCallback(array(ASCMS_MODULE_PATH . '/filesharing/index.class.php', 'Filesharing', 'uploadFinished'));

        //insert the uploader into the HTML-template
        $this->objTemplate->setVariable(array(
            'UPLOADER_CODE' => $uploader->getXHtml(),
            'EXTENDED_FILE_INPUT_CODE' => <<<CODE
<script type="text/javascript">
cx.include(
[
'core_modules/contact/js/extendedFileInput.js'
],
function() {
        var ef = new ExtendedFileInput({
                field: \$J('#file_upload')
        });
}
);
jQuery(document).ready(function($) {
    \$J('a.toggle').click(function() {
        \$J('div.toggle').toggle();
        return false;
    });
});
</script>
CODE
        ));

        // optional: initialize the widget displaying the folder contents
        $uploadId = $uploader->getUploadId();
        $tempPaths = self::getTemporaryFilePaths($uploadId);
        if (!is_dir($tempPaths[0] . '/' . $tempPaths[2])) {
            //\Cx\Lib\FileSystem::make_folder($tempPaths[0] . '/' . $tempPaths[2]);
            mkdir($tempPaths[0] . '/' . $tempPaths[2]);
            //\Cx\Lib\FileSystem::makeWritable($tempPaths[0] . '/' . $tempPaths[2]);
            chmod($tempPaths[0] . '/' . $tempPaths[2], 0777);
        }

        $folderWidget = UploadFactory::getInstance()->newFolderWidget($tempPaths[0] . '/' . $tempPaths[2], $uploaderInstanceName);
        $this->objTemplate->setVariable('UPLOAD_WIDGET_CODE', $folderWidget->getXHtml($uploaderFolderWidgetContainer, $uploaderInstanceName));

        // return the upload id
        return $uploadId;
    }

    /**
     * @param integer $uploadId the upload id of the active upload
     * @return array
     */
    public static function getTemporaryFilePaths($uploadId)
    {
        global $sessionObj;
        if (!isset($sessionObj)) $sessionObj = new cmsSession();

        return array(
            $sessionObj->getTempPath(),
            $sessionObj->getWebTempPath(),
            'filesharing_' . $uploadId,
        );
    }

    /**
     * the upload is finished
     * rewrite the names
     * write the uploaded files to the database
     *
     * @static
     * @param string $tempPath the temporary file path
     * @param string $tempWebPath the temporary file path which is accessable by web browser
     * @param array $data the data which are attached by uploader init method
     * @param integer $uploadId the upload id
     * @param $fileInfos
     * @param $response
     * @return array the target paths
     */
    public static function uploadFinished($tempPath, $tempWebPath, $data, $uploadId, $fileInfos, $response)
    {

        global $objDatabase;

        // the directory which will be made from the given cmd
        $directory = $data["directory"];

        // get target path
        // if the cmd is "downloads" add these files to the digital asset management module directory
        if ($directory == 'downloads') {
            $targetPath = ASCMS_DOWNLOADS_IMAGES_PATH;
            $targetPathWeb = ASCMS_DOWNLOADS_IMAGES_WEB_PATH;
        } else {
            $targetPath = ASCMS_FILESHARING_PATH . '/' . $directory;
            $targetPathWeb = ASCMS_FILESHARING_WEB_PATH . '/' . $directory;
        }

        // create target folder if the directory does not exist
        if (!is_dir($targetPath)) {
            \Cx\Lib\FileSystem::make_folder($targetPath);
            \Cx\Lib\FileSystem::makeWritable($targetPath);
        }

        // write the uploaded files into database
        $path = str_replace(ASCMS_PATH_OFFSET, '', $targetPathWeb);
        foreach ($fileInfos["originalFileNames"] as $cleanedName => $rawName) {
            $file = $cleanedName;
            $source = $path . '/' . $rawName;

            $hash = self::createHash();
            $check = self::createCheck($hash);

            $objDatabase->Execute("INSERT INTO " . DBPREFIX . "module_filesharing (`file`, `source`, `cmd`, `hash`, `check`, `upload_id`) VALUES (?, ?, ?, ?, ?, ?)", array($file, $source, $directory, $hash, $check, $uploadId));
        }

        $tempPaths = self::getTemporaryFilePaths($uploadId);

        // return web- and filesystem path. files will be moved there.
        return array($tempPaths[0] . '/' . $tempPaths[2], $tempPaths[1] . '/' . $tempPaths[2]);
    }

    /**
     * create check code
     *
     * @static
     * @param string $hash the hash of the file
     * @return string the check code
     */
    public static function createCheck($hash)
    {
        return md5(substr($hash, 0, 5));
    }

    /**
     * create the hash code
     *
     * @static
     * @return string the hash code
     */
    public static function createHash()
    {
        $hash = '';
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        for ($i = 0; $i < 10; $i++) {
            $hash .= $chars{rand(0, 62)};
        }
        return $hash;
    }

    /**
     * @static
     * @param integer $fileId
     * @return string the download link
     */
    public static function getDownloadLink($fileId)
    {
        global $objDatabase;
        $objResult = $objDatabase->SelectLimit("SELECT `cmd`, `hash` FROM " . DBPREFIX . "module_filesharing WHERE `id` = ?", 1, 0, array($fileId));
        $pageSlug = self::getFilesharingPageSlug($objResult->fields["cmd"]);
        $objUrl = clone \Env::get('Resolver')->getUrl();

        if ($objResult !== false) {
            return $objUrl->getDomain() . (ASCMS_PATH_OFFSET ? ASCMS_PATH_OFFSET . "/" : "") . $pageSlug . "?hash=" . $objResult->fields["hash"];
        } else {
            return false;
        }
    }

    /**
     * @static
     * @param string $cmd the cmd of the upload page
     * @return bool
     */
    private static function getFilesharingPageSlug($cmd)
    {
        global $_LANGID;
        $repo = Env::em()->getRepository('Cx\Model\ContentManager\Page');
        $page = $repo->findOneBy(array(
            'module' => 'filesharing',
            'cmd' => $cmd,
            'lang' => $_LANGID,
        ));
        // if the page with the upload cmd does not exist fall back to an other site with section filesharing
        if ($page == null) {
            $page = $repo->findOneBy(array(
                'module' => 'filesharing',
                'lang' => $_LANGID,
            ));
        }
        if ($page) {
            return $page->getSlug();
        } else {
            return false;
        }
    }

    /**
     * @static
     * @param integer $fileId
     * @return string the download link
     */
    public static function getDeleteLink($fileId)
    {
        global $objDatabase;
        $objResult = $objDatabase->SelectLimit("SELECT `cmd`, `hash`, `check` FROM " . DBPREFIX . "module_filesharing WHERE `id` = ?", 1, 0, array($fileId));
        $pageSlug = self::getFilesharingPageSlug($objResult->fields["cmd"]);
        $objUrl = clone \Env::get('Resolver')->getUrl();

        if ($objResult !== false) {
            return $objUrl->getDomain() . (ASCMS_PATH_OFFSET ? ASCMS_PATH_OFFSET . "/" : "") . $pageSlug . "?hash=" . $objResult->fields["hash"] . "&amp;check=" . $objResult->fields["check"];
        } else {
            return false;
        }
    }

    /**
     * @static
     * @param integer $fileId file id
     * @return bool is shared or not
     */
    public static function isShared($fileId = null, $fileSource = null)
    {
        global $objDatabase;
        if ($fileSource != NULL) {
            $objResult = $objDatabase->SelectLimit("SELECT `id` FROM " . DBPREFIX . "module_filesharing WHERE `source` = ?", 1, -1, array($fileSource));
            if ($objResult !== false && $objResult->RecordCount() > 0) {
                $fileId = $objResult->fields["id"];
            }
        }
        return self::getDownloadLink($fileId) && self::getDeleteLink($fileId) && $fileId;
    }

    /**
     * clean up the database and shared files
     * deletes expired files and none existing files
     *
     * @static
     */
    static public function cleanUp()
    {
        global $objDatabase;

        $arrToDelete = array();

        // get all files from database
        $objFiles = $objDatabase->Execute("SELECT `id`, `source`, `expiration_date` FROM " . DBPREFIX . "module_filesharing");
        if ($objFiles !== false) {
            while (!$objFiles->EOF) {
                // if the file is expired or does not exist
                if (($objFiles->fields["expiration_date"] < date('Y-m-d H:i:s')
                    && $objFiles->fields["expiration_date"] != NULL)
                    || !file_exists(ASCMS_PATH . ASCMS_PATH_OFFSET . $objFiles->fields["source"])
                ) {
                    $fileExists = file_exists(ASCMS_PATH . ASCMS_PATH_OFFSET . $objFiles->fields["source"]);
                    // if the file is only expired delete the file from directory
                    if ($fileExists) {
                        \Cx\Lib\FileSystem::delete_file(ASCMS_PATH . ASCMS_PATH_OFFSET . $objFiles->fields["source"]);
                    }
                    $arrToDelete[] = $objFiles->fields["id"];
                }
                $objFiles->moveNext();
            }
        }
        // delete all expired or not existing files
        $objDatabase->Execute("DELETE FROM " . DBPREFIX . "module_filesharing WHERE `id` IN (?)", array(implode(',', $arrToDelete)));
    }

    /**
     * send a mail to the email with the message
     *
     * @static
     * @param integer $uploadId the upload id
     * @param string $subject the subject of the mail for the recipient
     * @param string $email the recipient's mail address
     * @param null|string $message the message for the recipient
     */
    static public function sendMail($uploadId, $subject, $email, $message = null)
    {
        global $objDatabase, $_CONFIG;

        /**
         * get all file ids from the last upload
         */
        $objResult = $objDatabase->Execute("SELECT `id` FROM " . DBPREFIX . "module_filesharing WHERE `upload_id` = ?", array($uploadId));
        if ($objResult !== false && $objResult->RecordCount() > 0) {
            $files[] = $objResult->fields["id"];
        }

        if (!is_int($uploadId) && empty($files)) {
            $files[] = $uploadId;
        }

        /**
         * init mail data. Mail template, Mailsubject and PhpMailer
         */
        $objMail = $objDatabase->SelectLimit("SELECT `subject`, `content` FROM " . DBPREFIX . "module_filesharing_mail_template WHERE `lang_id` = ?", 1, -1, array(FRONTEND_LANG_ID));
        $content = str_replace(array(']]', '[['), array('}', '{'), $objMail->fields["content"]);

        if (empty($subject))
            $subject = $objMail->fields["subject"];

        if (@include_once ASCMS_LIBRARY_PATH . '/phpmailer/class.phpmailer.php') {
            $objMail = new phpmailer();

            /**
             * Load mail template and parse it
             */
            $objTemplate = new HTML_Template_Sigma('.');
            $objTemplate->setErrorHandling(PEAR_ERROR_DIE);
            $objTemplate->setTemplate($content);

            $objTemplate->setVariable(array(
                "DOMAIN" => $_CONFIG["domainUrl"],
                'MESSAGE' => $message,
            ));

            if ($objTemplate->blockExists('filesharing_file')) {
                foreach ($files as $file) {
                    $objTemplate->setVariable(array(
                        'FILE_DOWNLOAD' => self::getDownloadLink($file),
                    ));
                    $objTemplate->parse('filesharing_file');
                }
            }

            if ($_CONFIG['coreSmtpServer'] > 0 && @include_once ASCMS_CORE_PATH . '/SmtpSettings.class.php') {
                if (($arrSmtp = SmtpSettings::getSmtpAccount($_CONFIG['coreSmtpServer'])) !== false) {
                    $objMail->IsSMTP();
                    $objMail->Host = $arrSmtp['hostname'];
                    $objMail->Port = $arrSmtp['port'];
                    $objMail->SMTPAuth = true;
                    $objMail->Username = $arrSmtp['username'];
                    $objMail->Password = $arrSmtp['password'];
                }
            }

            $objMail->CharSet = CONTREXX_CHARSET;
            $objMail->From = $_CONFIG['coreAdminEmail'];
            $objMail->FromName = $_CONFIG['coreGlobalPageTitle'];

            $objMail->Subject = $subject;
            $objMail->Body = $objTemplate->get();
            $objMail->AddAddress($email);
            $objMail->Send();
            $objMail->ClearAddresses();
        }
    }
}

?>