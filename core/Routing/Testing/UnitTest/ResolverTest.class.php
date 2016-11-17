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
 * ResolverTest
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @author      SS4U <ss4u.comvation@gmail.com>
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  core_resolver
 */

namespace Cx\Core\Routing\Testing\UnitTest;

/**
 * ResolverTest
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @author      SS4U <ss4u.comvation@gmail.com>
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  core_resolver
 */
class ResolverTest extends \Cx\Core\Test\Model\Entity\DatabaseTestCase
{

    /**
     * Domain url of the installation
     *
     * @var string
     */
    protected $domainUrl;

    /**
     * Constructs a test case with the given name.
     *
     * @param string    $name
     * @param array     $data
     * @param string    $dataName
     */
    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        global $_CONFIG;

        parent::__construct($name, $data, $dataName);
        $this->dataSetFolder = $this->cx->getCodeBaseCorePath() . '/Routing/Testing/UnitTest/Data';

        // $_CONFIG is not defined in cli mode
        $this->domainUrl = ASCMS_PROTOCOL . '://' . $_CONFIG['domainUrl'] . $this->cx->getCodeBaseOffsetPath();
        $this->domainUrl = 'http://www.clx.local';
    }

    /**
     * Override the parent, returns null database operation class
     *
     * @return \PHPUnit_Extensions_Database_Operation_Null
     */
    public function getSetUpOperation()
    {
        return new \PHPUnit_Extensions_Database_Operation_Null();
    }

    /**
     * Sends the request to given urlSlug and
     * return the response header
     *
     * @param string $urlSlug Url string
     *
     * @return \HTTP_Request2_Response
     */
    protected function getResponse($urlSlug)
    {
        $url = new \Cx\Core\Routing\Url($this->domainUrl . $urlSlug);
        $url->setParams(array(
            'runTest'   => 1,
            'component' => 'Routing',
            'dataSet'   => 'DataSet',
        ));
        $request = new \HTTP_Request2(
             $url->toString(),
            \HTTP_Request2::METHOD_POST
        );
        $request->setConfig(array(
            'ssl_verify_host' => false,
            'ssl_verify_peer' => false,
            'follow_redirects' => true,
            'strict_redirects' => true,
        ));
        $response = $request->send();
        return $response;
    }

    /**
     * @dataProvider resolverDataProvider Data value provider
     */
    public function testResolver(
        $language = null,
        $inputSlug = '',
        $expectedSlug = null,
        $expectedCanonicalUrl = ''
    ) {
        if ($expectedSlug === null) {
            $expectedSlug = $inputSlug;
        }
        $expectedUrlString = $langCode = $urlString = '';
        if ($language !== null) {
            $langCode           = \FWLanguage::getLanguageCodeById($language);
            $urlString         .= '/' . $langCode;
            $expectedUrlString .= '/' . $langCode;
        }
        $urlString         .= '/'. $inputSlug;
        $expectedUrlString .= '/'. $expectedSlug;

        $response = $this->getResponse($urlString);

        $this->assertTrue($response->getStatus() == 200);

        $effectiveUrl       = new \Cx\Core\Routing\Url($response->getEffectiveUrl());
        $effectiveUrlString = (!empty($langCode) ? '/'. $effectiveUrl->getLangDir() : '')
                              . '/' . $effectiveUrl->getSuggestedTargetPath();
        $this->assertEquals($expectedUrlString, $effectiveUrlString);

        if (!empty($expectedCanonicalUrl)) {
            $this->assertNotNull($response->getHeader('link'));
            $canonicalUrl = '';
            $matches      = null;
            if (preg_match('/^<(.*)>;\srel=\"canonical\"$/', $response->getHeader('link'), $matches)) {
                $canonicalUrl = $matches[1];
            }
            $this->assertEquals(
                $this->domainUrl . $expectedCanonicalUrl,
                $canonicalUrl
            );
        }
    }

    /**
     * Test records for the testResolver method
     *
     * @return array
     */
    public function resolverDataProvider()
    {
        return array(
            // Content
            array(2, 'Simple-content-page', null, '/en/Simple-content-page'),
            // Application
            array(2, 'Simple-application-page'),
            // Fallback -> Content
            array(1, 'Fallback-to-content-page', null, '/de/Fallback-to-content-page'),
            // Fallback -> Application
            array(1, 'Fallback-to-application-page'),
            // Symlink -> Content
            array(2, 'Simple-symlink-to-content-page', null, '/en/Simple-content-page'),
            // Symlink -> Application
            array(2, 'Simple-symlink-to-application-page'),
            // Redirect -> Content
            array(2, 'Simple-redirect-to-content-page', 'Simple-content-page', '/en/Simple-content-page'),
            // Redirect -> Application
            array(2, 'Simple-redirect-to-application-page', 'Simple-application-page'),
            // Alias -> Content
            array(null, 'Simple-alias-for-content-page', 'Simple-alias-for-content-page', '/en/Simple-content-page'),
            // Alias -> Application
            array(null, 'Simple-alias-for-application-page', 'Simple-alias-for-application-page'),
            // Redirect -> Symlink -> Content
            array(2, 'Redirect-to-symlink-content-page', 'Simple-symlink-to-content-page', '/en/Simple-content-page'),
            // Redirect -> Symlink -> Application
            array(2, 'Redirect-to-symlink-application-page', 'Simple-symlink-to-application-page'),
            // Redirect -> Fallback -> Content
            array(1, 'Redirect-to-fallback-content-page', 'Fallback-to-content-page', '/de/Fallback-to-content-page'),
            // Redirect -> Fallback -> Application
            array(1, 'Redirect-to-fallback-application-page', 'Fallback-to-application-page'),
            // Fallback -> Symlink -> Content
            array(1, 'Fallback-symlink-to-content-page', null, '/de/Fallback-symlink-to-content-page'),
            // Fallback -> Symlink -> Application
            array(1, 'Fallback-symlink-to-application-page'),
            // Symlink -> Redirect -> Content
            array(2, 'Symlink-to-redirect-to-content', 'Simple-content-page', '/en/Simple-content-page'),
            // Symlink -> Redirect -> Application
            array(2, 'Symlink-to-redirect-to-application', 'Simple-application-page'),
            // Symlink -> Fallback -> Content
            array(1, 'Symlink-to-fallback-to-content', null, '/de/Fallback-to-content-page'),
            // Symlink -> Fallback -> Application
            array(1, 'Symlink-to-fallback-to-application'),
            // Alias -> Redirect -> Content
            array(null, 'alias-redirect-to-content-page', 'alias-redirect-to-content-page', '/en/Simple-content-page'),
            // Alias -> Redirect -> Application
            array(null, 'alias-redirect-to-application-page', 'alias-redirect-to-application-page'),
            // Alias -> Fallback -> Content
            array(null, 'alias-fallback-to-content-page', null, '/de/Fallback-to-content-page'),
            // Alias -> Fallback -> Application
            array(null, 'alias-fallback-to-application-page'),
            // Alias -> Symlink -> Content
            array(null, 'alias-symlink-to-content-page', 'alias-symlink-to-content-page', '/en/Simple-symlink-to-content-page'),
            // Alias -> Symlink -> Application
            array(null, 'alias-symlink-to-application-page', 'alias-symlink-to-application-page'),
            // Symlink -> Fallback -> Redirect -> Content
            array(1, 'symlink-fallback-to-redirect-to-content', 'Fallback-to-content-page', '/de/Fallback-to-content-page'),
            // Symlink -> Fallback -> Redirect -> Application
            array(1, 'symlink-fallback-to-redirect-to-application', 'Fallback-to-application-page'),
            // Symlink -> Redirect -> Fallback -> Content
            array(1, 'symlink-redirect-to-fallback-to-content', 'Fallback-to-content-page', '/de/Fallback-to-content-page'),
            // Symlink -> Redirect -> Fallback -> Application
            array(1, 'symlink-redirect-to-fallback-to-application', 'Fallback-to-application-page'),
            // Fallback -> Symlink -> Redirect -> Content
            array(1, 'Fallback-symlink-to-redirect-to-content', 'Fallback-to-content-page', '/de/Fallback-to-content-page'),
            // Fallback -> Symlink -> Redirect -> Application
            array(1, 'Fallback-symlink-to-redirect-to-application', 'Fallback-to-application-page'),
            // Fallback -> Redirect -> Symlink -> Content
            array(1, 'Fallback-redirect-to-symlink-content-page', 'Fallback-symlink-to-content-page', '/de/Fallback-symlink-to-content-page'),
            // Fallback -> Redirect -> Symlink -> Application
            array(1, 'Fallback-redirect-to-symlink-application-page', 'Fallback-symlink-to-application-page'),
            // Redirect -> Symlink -> Fallback -> Content
            array(1, 'redirect-to-symlink-to-fallback-to-content', 'Symlink-to-fallback-to-content', '/de/Fallback-to-content-page'),
            // Redirect -> Symlink -> Fallback -> Application
            array(1, 'redirect-to-symlink-to-fallback-to-application', 'Symlink-to-fallback-to-application'),
            // Redirect -> Fallback -> Symlink -> Content
            array(1, 'redirect-to-fallback-to-symlink-to-content', 'Fallback-symlink-to-content-page', '/de/Fallback-symlink-to-content-page'),
            // Redirect -> Fallback -> Symlink -> Application
            array(1, 'redirect-to-fallback-to-symlink-to-application', 'Fallback-symlink-to-application-page'),
            // Alias -> Symlink -> Redirect -> Content
            array(null, 'alias-symlink-redirect-to-content', 'alias-symlink-redirect-to-content', '/en/Symlink-to-redirect-to-content'),
            // Alias -> Symlink -> Redirect -> Application
            array(null, 'alias-symlink-to-redirect-to-application', 'alias-symlink-to-redirect-to-application'),
            // Alias -> Fallback -> Symlink -> Content
            array(null, 'alias-fallback-symlink-to-content-page', null, '/de/Fallback-symlink-to-content-page'),
            // Alias -> Fallback -> Symlink -> Application
            array(null, 'alias-fallback-symlink-to-application-page'),
            // Alias -> Fallback -> Redirect -> Content
            array(null, 'alias-fallback-redirect-to-content-page', null, '/de/Fallback-redirect-to-content-page'),
            // Alias -> Fallback -> Redirect -> Application
            array(null, 'alias-fallback-redirect-to-application-page'),
            // Alias -> Redirect -> Symlink -> Content
            array(null, 'alias-redirect-to-symlink-content-page', 'alias-redirect-to-symlink-content-page', '/en/Simple-symlink-to-content-page'),
            // Alias -> Redirect -> Symlink -> Application
            array(null, 'alias-redirect-to-symlink-application-page', 'alias-redirect-to-symlink-application-page'),
            // Alias -> Redirect -> Fallback -> Content
            array(null, 'alias-redirect-to-fallback-content-page', null, '/de/Fallback-to-content-page'),
            // Alias -> Redirect -> Fallback -> Application
            array(null, 'alias-redirect-to-fallback-application-page'),
            // Alias -> Symlink -> Redirect -> Fallback -> Content
            array(null, 'alias-symlink-redirect-to-fallback-to-content', null, '/de/symlink-redirect-to-fallback-to-content'),
            // Alias -> Symlink -> Redirect -> Fallback -> Application
            array(null, 'alias-symlink-redirect-to-fallback-to-application'),
            // Alias -> Symlink -> Fallback -> Redirect -> Content
            array(null, 'alias-symlink-fallback-to-redirect-to-content', null, '/de/symlink-fallback-to-redirect-to-content'),
            // Alias -> Symlink -> Fallback -> Redirect -> Application
            array(null, 'alias-symlink-fallback-to-redirect-to-application'),
            // Alias -> Fallback -> Redirect -> Symlink -> Content
            array(null, 'alias-Fallback-redirect-to-symlink-content-page', null, '/de/Fallback-redirect-to-symlink-content-page'),
            // Alias -> Fallback -> Redirect -> Symlink -> Application
            array(null, 'alias-Fallback-redirect-to-symlink-application-page'),
            // Alias -> Fallback -> Symlink -> Redirect -> Content
            array(null, 'alias-Fallback-symlink-to-redirect-to-content', null, '/de/Fallback-symlink-to-redirect-to-content'),
            // Alias -> Fallback -> Symlink -> Redirect -> Application
            array(null, 'alias-Fallback-symlink-to-redirect-to-application'),
            // Alias -> Redirect -> Fallback -> Symlink -> Content
            array(null, 'alias-redirect-to-fallback-to-symlink-to-content', null, '/de/Fallback-symlink-to-content-page'),
            // Alias -> Redirect -> Fallback -> Symlink -> Application
            array(null, 'alias-redirect-to-fallback-to-symlink-to-application'),
            // Alias -> Redirect -> Symlink -> Fallback -> Content
            array(null, 'alias-redirect-to-symlink-to-fallback-to-content', null, '/de/Symlink-to-fallback-to-content'),
            // Alias -> Redirect -> Symlink -> Fallback -> Application
            array(null, 'alias-redirect-to-symlink-to-fallback-to-application'),
            
            // duplicate slugs
            array(2, 'News'),
            array(2, 'Duplicate-News'),
            array(2, 'Home'),
            array(2, 'Duplicate-Home'),

            // test home page
            array(2, ''),

            // legacy page test
            array(2, 'index.php?section=Access', 'index.php'),
        );
    }

    public function testInExistPage()
    {
        $response = $this->getResponse('/de/not-exists-url');
        $this->assertTrue($response->getStatus() == 404);
    }

}
