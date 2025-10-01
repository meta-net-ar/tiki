<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Test\Core\Multilingual\Aligner;

use Multilingual_Aligner_SentenceSegmentor;
use TikiTestCase;

/**
 * @group unit
 *
 */

class SentenceSegmentorTest extends TikiTestCase
{
    ////////////////////////////////////////////////////////////////
    // Documentation tests
    //    These tests illustrate how to use this class.
    ////////////////////////////////////////////////////////////////

    /**
     * @group multilingual
     */
    public function thisIsHowYouCreateAsentenceSegmentor()
    {
        $segmentor = new Multilingual_Aligner_SentenceSegmentor();
    }

    /**
     * @group multilingual
     */
    public function thisIsHowYouSegmentTextIntoSentences()
    {
        $segmentor = new Multilingual_Aligner_SentenceSegmentor();
        $text = "hello. world";
        $sentences = $segmentor->segment($text);
    }

    ////////////////////////////////////////////////////////////////
    // Internal tests
    //    These tests check the internal workings of the class.
    ////////////////////////////////////////////////////////////////


    /**
     * @group multilingual
     */
    public function testSegmentationDealsWithPeriod()
    {
        $text = "hello brand new. world.";
        $expSentences = ["hello brand new.", " world."];
        $this->doTestBasicSegmentation(
            $text,
            $expSentences,
            "Segmentation did not deal properly with separation with period."
        );
    }

    /**
     * @group multilingual
     */
    public function testSegmentationDealsWithQuestionMark()
    {
        $text = "hello? Anybody home?";
        $expSentences = ["hello?", " Anybody home?"];
        $this->doTestBasicSegmentation(
            $text,
            $expSentences,
            "Segmentation did not deal properly with separation with question mark."
        );
    }

    /**
     * @group multilingual
     */
    public function testSegmentationDealsWithSeveralQuestionMarks()
    {
        $text = "hello???? Anybody home?";
        $expSentences = ["hello????", " Anybody home?"];
        $this->doTestBasicSegmentation(
            $text,
            $expSentences,
            "Segmentation did not deal properly with separation with question mark."
        );
    }

    /**
     * @group multilingual
     */
    public function testSegmentationDealsWithExclamationMark()
    {
        $text = "hello! Anybody home!";
        $expSentences = ["hello!", " Anybody home!"];
        $this->doTestBasicSegmentation(
            $text,
            $expSentences,
            "Segmentation did not deal properly with separation with exclamation mark."
        );
    }


    /**
     * @group multilingual
     */
    public function testSegmentationDealsWithMixOfExclamationAndQuestionMarks()
    {
        $text = "hello?!? Anybody home!";
        $expSentences = ["hello?!?", " Anybody home!"];

        $this->doTestBasicSegmentation(
            $text,
            $expSentences,
            "Segmentation did not deal properly with separation with exclamation mark."
        );
    }


    /**
     * @group multilingual
     */
    public function testSegmentationDealsWithEmptyString()
    {
        $text = "";
        $expSentences = [];
        $this->doTestBasicSegmentation(
            $text,
            $expSentences,
            "Segmentation did not deal properly with empty string."
        );
    }

    /**
     * @group multilingual
     */
    public function testSegmentationDealsWithWikiParagraphBreak()
    {
        $text = "This sentence ends with a period and a newline.\n" .
                        "This sentence has no period, but ends with a wiki paragraph break\n\n" .
                        "This is the start of a new paragraph.";

        $expSentences = [
                        "This sentence ends with a period and a newline.",
                        "\nThis sentence has no period, but ends with a wiki paragraph break\n\n",
                        "This is the start of a new paragraph."
        ];

        $this->doTestBasicSegmentation(
            $text,
            $expSentences,
            "Segmentation did not deal properly with wiki paragraph break."
        );
    }

    /**
     * @group multilingual
     */
    public function testSegmentationDealsWithBulletLists()
    {
        $text = "This sentence precedes a bullet list.\n" .
                    "* Bullet 1\n" .
                    "** Bullet 1-1\n" .
                    "* Bullet 2\n" .
                    "After bullet list";

        $expSentences = [
                    "This sentence precedes a bullet list.",
                    "\n",
                    "* Bullet 1\n",
                    "** Bullet 1-1\n",
                    "* Bullet 2\nAfter bullet list"];

        $this->doTestBasicSegmentation(
            $text,
            $expSentences,
            "Segmentation did not deal properly with bullet list."
        );
    }

    ////////////////////////////////////////////////////////////////
    // Helper methods
    ////////////////////////////////////////////////////////////////

    public function doTestBasicSegmentation($text, $expSentences, $message)
    {
        $segmentor = new Multilingual_Aligner_SentenceSegmentor();
        $sentences = $segmentor->segment($text);
        $got_sentences_as_string = implode(', ', $sentences);
        $exp_sentences_as_string = implode(', ', $expSentences);

        $this->assertEquals(
            $expSentences,
            $sentences,
            $message . "\n" .
            "Segmented sentences differed from expected.\n" .
            "Expected Sentences: $exp_sentences_as_string\n" .
            "Got      Sentences: $got_sentences_as_string\n"
        );
    }
}
