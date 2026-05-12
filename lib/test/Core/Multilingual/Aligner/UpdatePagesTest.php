<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Test\Core\Multilingual\Aligner;

use Multilingual_Aligner_MockMTWrapper;
use Multilingual_Aligner_SentenceAlignments;
use Multilingual_Aligner_UpdatePages;
use TikiTestCase;

/**
 * @group unit
 * @group MultilingualAlignerUpdatePages
 * @group slow
 *
 */

class UpdatePagesTest extends TikiTestCase
{
    public $updater;
    private $orig_source_sentences =
        [
                "Firefox supports international characters for languages such as Hindi.",
                "You can test your Firefoxs support of Hindi scripts at BBC Hindi.",
                "Most sites that require additional fonts will have a page describing where you can get the font.",
                ];
    private $orig_target_sentences =
        [
                "Firefox supporte les caractères internationaux pour des langues tel que lindien.",
                "Vous pouvez tester le support Firefox des scripts indiens sur BBC indien.",
                "La plupart des sites qui ont besoin de polices supplémentaires vont avoir une page qui décrit ou vous pouvez obtenir la police."
                ];
    private $source_alignment = "Firefox supports international characters for languages such as Hindi.<br/>You can test your Firefoxs support of Hindi scripts at BBC Hindi.<br/>Most sites that require additional fonts will have a page describing where you can get the font.";
    private $target_alignment = "Firefox supporte les caractères internationaux pour des langues tel que lindien.<br/>Vous pouvez tester le support Firefox des scripts indiens sur BBC indien.<br/>La plupart des sites qui ont besoin de polices supplémentaires vont avoir une page qui décrit ou vous pouvez obtenir la police.";

    private $extra_source_sentence = "This sentence was added on the source side.";
    private $mt_extra_source_sentence = "Cette phrase a été ajoutée du côté source.";

    private $extra_target_sentence = "Cette phrase a été ajoutée du côté source.";
    private $mt_extra_target_sentence = "This sentence was added on the target side.";

    protected function setUp(): void
    {
        $this->updater = new Multilingual_Aligner_UpdatePages();
        $this->updater->alignments = new Multilingual_Aligner_SentenceAlignments();
        $this->updater->translator = new Multilingual_Aligner_MockMTWrapper();

        $this->updater->translator->SetMT(
            $this->extra_source_sentence . "<br/>" . $this->mt_extra_target_sentence,
            $this->mt_extra_source_sentence . "<br/>" . $this->extra_target_sentence,
            'en',
            'fr'
        );
    }


    public function disabledTestReminder()
    {
        $this->fail("remember to reactivate all tests in UpdateSentences");
    }

    ////////////////////////////////////////////////////////////////
    // Documentation tests
    //    These tests illustrate how to use this class.
    ////////////////////////////////////////////////////////////////

    public function thisIsHowYouCreateAupdatePages()
    {
        $test = new Multilingual_Aligner_UpdatePages();
    }

    ////////////////////////////////////////////////////////////////
    // Note: In the rest of these tests, you can assume that
    //       $this->updater is an instance of UpdatePages
    //       created as above.
    ////////////////////////////////////////////////////////////////

    public function testSentenceAddedOnSourceSideOnly()
    {
        $source_lng = "en";
        $target_lng = "fr";

        $source_original_array = $this->orig_source_sentences;
        $source_modified_array = $this->orig_source_sentences;
        $source_modified_array = $this->insertSentenceAtIndex(1, $this->extra_source_sentence, $source_modified_array);

        $target_original_array = $this->orig_target_sentences;
        $target_modified_array = $target_original_array;

        $expected_content = $this->insertSentenceAtIndex(1, "Added_Source " . $this->mt_extra_source_sentence, $target_modified_array);

        $this->doTestBasicUpdating(
            $source_lng,
            $target_lng,
            $source_original_array,
            $source_modified_array,
            $target_original_array,
            $target_modified_array,
            $expected_content,
            ""
        );
    }


    public function testSentenceInsertedOnTargetSideOnly()
    {
        $source_lng = "en";
        $target_lng = "fr";

        $source_original_array = $this->orig_source_sentences;
        $source_modified_array = $this->orig_source_sentences;

        $target_original_array = $this->orig_target_sentences;
        $target_modified_array = $target_original_array;
        $target_modified_array = $this->insertSentenceAtIndex(1, $this->extra_target_sentence, $target_modified_array);

        $expected_content = $target_modified_array;

        $this->doTestBasicUpdating(
            $source_lng,
            $target_lng,
            $source_original_array,
            $source_modified_array,
            $target_original_array,
            $target_modified_array,
            $expected_content,
            ""
        );
    }


    public function testSentenceDeletedOnSourceSideOnly()
    {
        $source_lng = "en";
        $target_lng = "fr";

        $source_original_array = $this->orig_source_sentences;
        $source_modified_array = $this->orig_source_sentences;
        $source_modified_array = $this->removeSentenceAtIndex(1, $source_modified_array);

        $target_original_array = $this->orig_target_sentences;
        $target_modified_array = $target_original_array;

        $expected_content = $target_modified_array;
        $expected_content [1] = "Deleted_Source " . $expected_content [1];

        $this->doTestBasicUpdating(
            $source_lng,
            $target_lng,
            $source_original_array,
            $source_modified_array,
            $target_original_array,
            $target_modified_array,
            $expected_content,
            ""
        );
    }

    public function testSentenceDeletedOnTargetSideOnly()
    {
        $source_lng = "en";
        $target_lng = "fr";

        $source_original_array = $this->orig_source_sentences;
        $source_modified_array = $this->orig_source_sentences;

        $target_original_array = $this->orig_target_sentences;
        $target_modified_array = $target_original_array;
        $target_modified_array = $this->removeSentenceAtIndex(1, $target_modified_array);

        $expected_content = $target_original_array;
        $expected_content [1] = "Deleted_Target " . $expected_content [1];

        $this->doTestBasicUpdating(
            $source_lng,
            $target_lng,
            $source_original_array,
            $source_modified_array,
            $target_original_array,
            $target_modified_array,
            $expected_content,
            ""
        );
    }

    /**
     * @group marked-as-skipped
     */
    public function testSentenceInsertedInBothSourceAndTargetSides()
    {
        $this->markTestSkipped("This test is failing at the moment. Need to fix it");

        $source_lng = "en";
        $target_lng = "fr";

        $source_original_array = $this->orig_source_sentences;
        $source_modified_array = $this->orig_source_sentences;
        $source_modified_array = $this->insertSentenceAtIndex(1, $this->extra_source_sentence, $source_modified_array);

        $target_original_array = $this->orig_target_sentences;
        $target_modified_array = $this->orig_target_sentences;
        $target_modified_array = $this->insertSentenceAtIndex(2, $this->extra_target_sentence, $target_modified_array);

        $expected_content = $this->insertSentenceAtIndex(1, "Added_Source " . $this->mt_extra_source_sentence, $target_modified_array);

        $this->doTestBasicUpdating(
            $source_lng,
            $target_lng,
            $source_original_array,
            $source_modified_array,
            $target_original_array,
            $target_modified_array,
            $expected_content,
            ""
        );
    }

    /**
     * @group marked-as-skipped
     */
    public function testSentenceDeletedOnBothSourceAndTargetSides()
    {
        $this->markTestSkipped("This test is failing at the moment. Need to fix it");

        $source_lng = "en";
        $target_lng = "fr";

        $source_original_array = $this->orig_source_sentences;
        $source_modified_array = $this->orig_source_sentences;
        $source_modified_array = $this->removeSentenceAtIndex(1, $source_modified_array);

        $target_original_array = $this->orig_target_sentences;
        $target_modified_array = $this->orig_target_sentences;
        $target_modified_array = $this->removeSentenceAtIndex(2, $target_modified_array);

        $expected_content = $target_original_array;
        $expected_content [1] = "Deleted_Source " . $expected_content [1];
        $expected_content [2] = "Deleted_Target " . $expected_content [2];

        $this->doTestBasicUpdating(
            $source_lng,
            $target_lng,
            $source_original_array,
            $source_modified_array,
            $target_original_array,
            $target_modified_array,
            $expected_content,
            ""
        );
    }


    public function doTestBasicUpdating(
        $source_lng,
        $target_lng,
        $orig_source_array,
        $modified_source_array,
        $orig_target_array,
        $modified_target_array,
        $expected_updated_target_array,
        $message
    ) {



        $orig_source = implode(' ', $orig_source_array);
        $modified_source = implode(' ', $modified_source_array);
        $orig_target = implode(' ', $orig_target_array);
        $modified_target = implode(' ', $modified_target_array);


        $this->updater->SetAlignment($this->source_alignment, $this->target_alignment, $source_lng, $target_lng);
        $final = $this->updater->UpdatingTargetPage(
            $orig_source,
            $modified_source,
            $orig_target,
            $modified_target,
            $source_lng,
            $target_lng
        );



        $this->assertEquals($expected_updated_target_array, $final, $message);
    }


    ////////////////////////////////////////////////////////////////
    // Helper functions.
    ////////////////////////////////////////////////////////////////

    public function insertSentenceAtIndex($index, $sentenceToAdd, $sentenceList)
    {
        $modifiedSentenceList = [];
        foreach ($sentenceList as $ii => $iiValue) {
            if ($ii === $index) {
                $modifiedSentenceList[] = $sentenceToAdd;
            }
            $modifiedSentenceList[] = $iiValue;
        }
        return $modifiedSentenceList;
    }

    public function removeSentenceAtIndex($index, $sentenceList)
    {
        array_splice($sentenceList, $index, 1);
        return $sentenceList;
    }
}
