<?php

namespace Tiki\MailIn\Action;

use Services_Tracker_Utilities;
use Tiki\MailIn\Account;
use Tiki\MailIn\Source\Message;
use TikiLib;
use Tracker_Definition;


class Tracker implements ActionInterface
{
	private $tracker;
	private $attachments;

	/**
	 * Tracker constructor.
	 * @param array $params
	 */
	public function __construct(array $params)
	{
		$this->tracker = isset($params['trackerId']) ? intval($params['trackerId']) : 0;
		$this->attachments = $params['attachments'];
	}


	function getName()
	{
		return tr('Store mail in Tracker');
	}

	function isEnabled()
	{
		global $prefs;

		return $prefs['feature_trackers'] == 'y';
	}

	function isAllowed(Account $account, Message $message)
	{
		$user = $message->getAssociatedUser();
		$perms = TikiLib::lib('tiki')->get_user_permission_accessor($user, 'tracker', $this->tracker);
		if(!$perms->tiki_p_view_trackers || !$perms->tiki_p_create_tracker_items){
			return false;
		}
		return true;
	}

	function createTracker($from)
	{
		$trackerUtilities = new Services_Tracker_Utilities();
		$trackerData = [
			'name' => $from,
			'description' => '',
			'descriptionIsParsed' => 'n'
		];
		$trackerId = $trackerUtilities->createTracker($trackerData);

		return $trackerId;
	}


	function execute(Account $account, Message $message)
	{
		$tikilib = TikiLib::lib('tiki');
		$trackerUtilities = new Services_Tracker_Utilities();

		$fieldSubject = [
			'name' => 'subject',
			'type' => 't',
			'isMandatory' => false,
			'description' => '',
			'descriptionIsParsed' => '',
			'permName' => null,
			'options' => null,
		];

		$fieldUser = [
			'name' => 'user',
			'type' => 'u',
			'isMandatory' => false,
			'description' => '',
			'descriptionIsParsed' => '',
			'permName' => null,
			'options' => null,
		];

		$from = $message->getFromAddress();
		$fieldFrom = [
			'name' => 'from',
			'type' => 'm',
			'isMandatory' => true,
			'description' => '',
			'descriptionIsParsed' => '',
			'permName' => null,
			'options' => null,
		];

		$fieldTo = [
			'name' => 'to',
			'type' => 'm',
			'isMandatory' => true,
			'description' => '',
			'descriptionIsParsed' => '',
			'permName' => null,
			'options' => null,
		];

		$description = "Created from " . $message->getFromAddress();
		$fieldDescription = [
			'name' => 'description',
			'type' => 'a',
			'isMandatory' => false,
			'description' => '',
			'descriptionIsParsed' => '',
			'permName' => null,
			'options' => null,
		];


		$fieldDate = [
			'name' => 'date',
			'type' => 'f',
			'isMandatory' => true,
			'description' => '',
			'descriptionIsParsed' => '',
			'permName' => null,
			'options' => null,
		];

		$fieldBody = [
			'name' => 'body',
			'type' => 'a',
			'isMandatory' => false,
			'description' => '',
			'descriptionIsParsed' => '',
			'permName' => null,
			'options' => null,
		];

		$fieldAttachments = [
			'name' => 'attachments',
			'type' => 'FG',
			'isMandatory' => false,
			'description' => '',
			'descriptionIsParsed' => '',
			'permName' => null,
			'options' => null,
		];

		$data = [
			$fieldSubject,
			$fieldUser,
			$fieldFrom,
			$fieldTo,
			$fieldDescription,
			$fieldDate,
			$fieldBody,
			$fieldAttachments
		];

		$datasItem = [
			$message->getSubject(),
			$message->getAssociatedUser(),
			$message->getFromAddress(),
			$message->getRecipientAddress(),
			$description,
			$tikilib->now,
			$message->getBody(),
		];

		if ($this->tracker == 0) {
			// create new tracker
			$trackerId = $this->createTracker($from);

			// create tracker fields
			$permNames=[];
			foreach ($data as $fieldData){
				$fieldData['trackerId'] = $trackerId;
				$fieldId = $trackerUtilities->createField($fieldData);
				// build permNames table
				$permNames[]= 'f_' . $fieldId;
			}

			$definition = $this->getDefinition($trackerId);

			$itemFiles='';

			if ($this->canAttach() && $account->hasAutoAttach()) {
				$i=0;
				foreach ($message->getAttachments() as $att) {
					// store each attachment
					$id = $this->attachFile($att,  $message->getAssociatedUser());
					if ($i == 0) {
						$itemFiles .= $id;
					} else {
						$itemFiles .= ',' . $id;
					}
					$i++;
				}
			}

			$datasItem[] = $itemFiles;
			$toItem = [];
			// construct final table
			for ($i=0; $i < sizeof($permNames);$i++) {
				$toItem[$permNames[$i]]= $datasItem[$i];
			}

			$itemId = $trackerUtilities->insertItem(
				$definition,
				[
					'status' => null,
					'fields' => $toItem,
				]
			);
		}
		return true;
	}

	function canAttach()
	{
		global $prefs;
		if ($prefs['trackerfield_files'] != 'y') {
			return false;
		}

		return true;
	}

	private function attachFile($att, $user)
	{
		$trklib = TikiLib::lib('trk');
		$attId = $trklib->tracker_item_file($att['name'], $att['type'], $att['size'], $att['data'], "inserted by Mailin to Tracker $user", $user, '');
		return $attId;
	}

	private function getDefinition($trackerId)
	{
		$trklib = TikiLib::lib('trk');
		$fields = $trklib->list_tracker_fields($trackerId, 0, -1, 'position_asc', '', false /* Translation must be done from the views to avoid translating the sources on edit. */);

		$definition = Tracker_Definition::get($trackerId);

		$definition->setFields($fields['data']);
		return $definition;
	}
}