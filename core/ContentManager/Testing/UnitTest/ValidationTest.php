<?php

class ValidationTest extends \Cx\Core\Test\Model\Entity\DoctrineTestCase
{
    /**
     * @expectedException \Cx\Model\Base\ValidationException
     */
    public function testValidationException() {
        $p = new \Cx\Core\ContentManager\Model\Entity\Page();
        $n = new \Cx\Core\ContentManager\Model\Entity\Node();

        $p->setLang(1);
        $p->setTitle('testpage');
        $p->setNode($n);

        //set disallowed module name
        $p->setModule('1|@f2');

        self::$em->persist($n);
        self::$em->persist($p);

        //should raise exception
        self::$em->flush();
    }
}