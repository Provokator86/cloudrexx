<?php

/**
 * Cloudrexx
 *
 * @link      http://www.cloudrexx.com
 * @copyright Cloudrexx AG 2007-2015
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Cloudrexx" is a registered trademark of Cloudrexx AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */

/**
 * Main controller for News
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_news
 */

namespace Cx\Core_Modules\News\Controller;

/**
 * Main controller for News
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_news
 */
class ComponentController extends \Cx\Core\Core\Model\Entity\SystemComponentController {
    public function getControllerClasses() {
        // Return an empty array here to let the component handler know that there
        // does not exist a backend, nor a frontend controller of this component.
        return array('EsiWidget');
    }

     /**
     * {@inheritdoc}
     */
    public function getControllersAccessableByJson() {
        return array('JsonNews', 'EsiWidgetController');
    }

    /**
     * Returns a list of command mode commands provided by this component
     *
     * @return array List of command names
     */
    public function getCommandsForCommandMode() {
        return array('News');
    }

    /**
     * Execute api command
     *
     * @param string $command Name of command to execute
     * @param array  $arguments List of arguments for the command
     * @param array  $dataArguments (optional) List of data arguments for the command
     */
    public function executeCommand($command, $arguments, $dataArguments = array()) {
        $subcommand = null;
        if (!empty($arguments[0])) {
            $subcommand = $arguments[0];
        }

        // define frontend language
        if (!defined('FRONTEND_LANG_ID')) {
            define('FRONTEND_LANG_ID', 1);
        }

        switch ($command) {
            case 'News':
                switch ($subcommand) {
                    case 'Cron':
                        $objNews = new NewsManager();
                        $objNews->createRSS();
                        break;
                }
                break;
            default:
                break;
        }
    }

    /**
     * Load your component.
     *
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page       The resolved page
     */
    public function load(\Cx\Core\ContentManager\Model\Entity\Page $page) {
        global $_CORELANG, $objTemplate, $subMenuTitle;
        switch ($this->cx->getMode()) {
            case \Cx\Core\Core\Controller\Cx::MODE_FRONTEND:
                $newsObj = new News($page->getContent());
                $page->setContent($newsObj->getNewsPage());
                $newsObj->getPageTitle($page->getTitle());

                if (substr($page->getCmd(), 0, 7) == 'details') {
                    $page->setTitle($newsObj->newsTitle);
                    $page->setContentTitle($newsObj->newsTitle);
                    $page->setMetaTitle($newsObj->newsTitle);
                    $page->setMetakeys($newsObj->newsMetaKeys);

                    // Set the meta page description to the teaser text if displaying news details
                    $teaser = $newsObj->getTeaser();
                    if ($teaser) {
                        $page->setMetadesc(contrexx_raw2xhtml(contrexx_strip_tags(html_entity_decode($teaser, ENT_QUOTES, CONTREXX_CHARSET))));
                    } else {
                        $page->setMetadesc(contrexx_raw2xhtml(contrexx_strip_tags(html_entity_decode($newsObj->newsText, ENT_QUOTES, CONTREXX_CHARSET))));
                    }

                    // Set the meta page image to the thumbnail if displaying news details
                    $image = $newsObj->newsThumbnail;
                    if ($image) {
                        $page->setMetaimage($image);
                    }
                }
                break;

            case \Cx\Core\Core\Controller\Cx::MODE_BACKEND:
                $this->cx->getTemplate()->addBlockfile('CONTENT_OUTPUT', 'content_master', 'LegacyContentMaster.html');
                $objTemplate = $this->cx->getTemplate();

                \Permission::checkAccess(10, 'static');
                $subMenuTitle = $_CORELANG['TXT_NEWS_MANAGER'];
                $objNews      = new NewsManager();
                $objNews->getPage();
                break;

            default:
                break;
        }
    }

    /**
     * Do something after system initialization
     *
     * USE CAREFULLY, DO NOT DO ANYTHING COSTLY HERE!
     * CALCULATE YOUR STUFF AS LATE AS POSSIBLE.
     * This event must be registered in the postInit-Hook definition
     * file config/postInitHooks.yml.
     *
     * @param \Cx\Core\Core\Controller\Cx   $cx The instance of \Cx\Core\Core\Controller\Cx
     */
    public function postInit()
    {
        $widgetController = $this->getComponent('Widget');
        // Get Headlines
        for ($i = 1; $i <= 10; $i++) {
            $id = '';
            if ($i > 1) {
                $id = $i;
            }
            $widgetController->createWidget(
                $this,
                'HEADLINES' . $id . '_FILE',
                false,
                '',
                '',
                array(),
                \Cx\Core_Modules\Widget\Model\Entity\EsiWidget::ESI_VAR_ID_USER
            );
        }

        // Get Top news
        $widgetController->createWidget(
            $this,
            'TOP_NEWS_FILE',
            false,
            '',
            '',
            array(),
            \Cx\Core_Modules\Widget\Model\Entity\EsiWidget::ESI_VAR_ID_USER
        );

        // Get News categories
        $widgetController->createWidget(
            $this,
            'NEWS_CATEGORIES'
        );

        // Get News Archives
        $widgetController->createWidget(
            $this,
            'NEWS_ARCHIVES',
            false,
            '',
            '',
            array(),
            \Cx\Core_Modules\Widget\Model\Entity\EsiWidget::ESI_VAR_ID_USER
        );

        // Get recent News Comments
        $widgetController->createWidget(
            $this,
            'NEWS_RECENT_COMMENTS_FILE'
        );

        // Set news teasers
        $teaser      = new Teasers();
        $teaserNames = array_flip($teaser->arrTeaserFrameNames);
        if (empty($teaserNames)) {
            return;
        }
        foreach ($teaserNames as $teaserName) {
            $widgetController->createWidget(
                $this,
                'TEASERS_' . $teaserName,
                false,
                '',
                '',
                array(),
                \Cx\Core_Modules\Widget\Model\Entity\EsiWidget::ESI_VAR_ID_USER
            );
        }
    }

    /**
     * Do something for search the content
     *
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page       The resolved page
     */
    public function preContentParse(\Cx\Core\ContentManager\Model\Entity\Page $page) {
        $this->cx->getEvents()->addEventListener('SearchFindContent', new \Cx\Core_Modules\News\Model\Event\NewsEventListener());
    }

    /**
     * Register the Event listeners
     */
    public function registerEventListeners() {
        $newsEventListener = new \Cx\Core_Modules\News\Model\Event\NewsEventListener();
        $this->cx->getEvents()->addEventListener('languageStatusUpdate', $newsEventListener);
    }
}
