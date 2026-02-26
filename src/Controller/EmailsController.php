<?php

namespace SureLv\Emails\Controller;

use SureLv\Emails\Dto\ListMemberStatusChangeDto;
use SureLv\Emails\Entity\TypeUnsubscribe;
use SureLv\Emails\Enum\EmailEventType;
use SureLv\Emails\Enum\EmailTrackingType;
use SureLv\Emails\Enum\ListMemberStatus;
use SureLv\Emails\Model\EmailsListMemberModel;
use SureLv\Emails\Model\TrackingModel;
use SureLv\Emails\Model\TypeUnsubscribeModel;
use SureLv\Emails\Service\EmailEventService;
use SureLv\Emails\Service\EmailsHelperService;
use SureLv\Emails\Service\EmailStatusUpdater;
use SureLv\Emails\Service\ModelService;
use Aws\Sns\Message;
use Aws\Sns\MessageValidator;
use SureLv\Emails\Service\EmailsLogger;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;

class EmailsController
{

    public function snsEndpoint(Request $request, EmailsLogger $logger, EmailStatusUpdater $emailStatusUpdater): Response
	{
		// $message = '{"Type":"SubscriptionConfirmation","MessageId":"74a666f6-8e16-425e-b1af-2641d7007f69","Token":"2336412f37fb687f5d51e6e2425c464de1288763e7fc93be780aa845a32644c931f8cb8f8d9cc96d0cc4d5869f38a600a9ed0f7e76054f6003ea2044d59be3a359b90c734da41316863f110bfbd1579837e18f73f891ac2c688df79af04d762d992a1b46071b42a03f391ce135a2851d6ff4615140bf718703e5cdfe3187b529","TopicArn":"arn:aws:sns:us-west-2:343407400825:SESGenecyNotifications","Message":"You have chosen to subscribe to the topic arn:aws:sns:us-west-2:343407400825:SESGenecyNotifications.\nTo confirm the subscription, visit the SubscribeURL included in this message.","SubscribeURL":"https://sns.us-west-2.amazonaws.com/?Action=ConfirmSubscription&TopicArn=arn:aws:sns:us-west-2:343407400825:SESGenecyNotifications&Token=2336412f37fb687f5d51e6e2425c464de1288763e7fc93be780aa845a32644c931f8cb8f8d9cc96d0cc4d5869f38a600a9ed0f7e76054f6003ea2044d59be3a359b90c734da41316863f110bfbd1579837e18f73f891ac2c688df79af04d762d992a1b46071b42a03f391ce135a2851d6ff4615140bf718703e5cdfe3187b529","Timestamp":"2023-04-16T18:27:31.932Z","SignatureVersion":"1","Signature":"42RizeqS+oASivma9D19TPbPPv0lAX5sllsWzlleRTlq9AyliPZeqqn8upnJFAUFh1f3qaaFPh4ehjTAclri0B5hEoTltVqz5QR9v7LgiEmEmZzGR27wgz5Gv4uq6tMfAAq4DRpVPb2vn5iM+uVmA4DPcCxiWEdICj0Kb7XXwSAg7oEg1dCxQWYfUt1PUE06dhHE08Qz9HVkzdn6jyudtLuCdMqDiV1IQXjihJzaMu8Zv6PttfzAe8KqiGymGSNrr7PTWd+rCOAUaDtghZy/Hr1OIMUxb+HIzjnO36+ChWI+FcQv9bZ4myztPb+er63sfIrK8p+YMXotxHOfIoZFIA==","SigningCertURL":"https://sns.us-west-2.amazonaws.com/SimpleNotificationService-56e67fcb41f6fec09b0196692625d385.pem"}';
		// $message = Message::fromJsonString($message);
		$logger->logInfo('SNS email endpoint called', array('request' => $request->request->all()));
		try {
			$message = Message::fromRawPostData();
		} catch (\Exception $e) {
			$logger->logCritical('Invalid POST data', array('exception' => $e->getMessage()));
			return new Response('Invalid POST data', 400);
		}

		$validator = new MessageValidator();
		try {
			$validator->validate($message);
		} catch (\Exception $e) {
			$logger->logCritical('Invalid SNS message', array('exception' => $e->getMessage()));
			return new Response('Invalid message', 400);
		}

		switch ($message['Type']) {

			case 'SubscriptionConfirmation':
				file_get_contents($message['SubscribeURL']);
				$logger->logInfo('SNS subscription confirmed', array('message' => $message));
				break;

			case 'Notification':
				$notification = json_decode($message['Message'], true);
				if (!is_array($notification) || !isset($notification['eventType'])) {
					$logger->logCritical('Invalid SNS notification', array('message' => $message['Message']));
					return new Response('Invalid notification', 400);
				}
				$eventType = $notification['eventType'];
				$logger->logInfo('SNS notification', array('eventType' => $eventType, 'notification' => $notification));
				$mailData = $notification['mail'] ?? [];
				if ($eventType === 'Bounce') {
					$emailStatusUpdater->handleBounce($notification['bounce'] ?? [], $mailData);
				} elseif ($eventType === 'Complaint') {
					$emailStatusUpdater->handleComplaint($notification['complaint'] ?? [], $mailData);
				} elseif ($eventType === 'Delivery') {
					$emailStatusUpdater->handleDelivery($mailData);
				}
				break;
            
			default:
				$logger->logCritical('Unknown SNS message type', array('message' => $message));
				return new Response('Unknown message type', 400);
            
		}
		return new Response('OK');
	}

	public function unsubscribe(int $memberId, int $messageId, string $payload, string $signature, Request $request, ModelService $modelService, EmailsHelperService $emailsHelperService, EmailEventService $emailEventService, MessageBusInterface $bus): Response
	{
		$token = $payload . '~' . $signature;
		$params = $emailsHelperService->getParamsFromPayloadToken($token);

		if (!is_array($params) || !isset($params['mi']) || !isset($params['i'])) {
			throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException('Invalid token');
		}

		if (intval($params['i']) !== $memberId || intval($params['mi']) !== $messageId) {
			throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException('Invalid unsubscribe link');
		}

		$subType = $params['s'] ?? null;

		$emailsListMemberModel = $modelService->getModel(EmailsListMemberModel::class); /** @var \SureLv\Emails\Model\EmailsListMemberModel $emailsListMemberModel */
		$typeUnsubscribeModel = $modelService->getModel(TypeUnsubscribeModel::class); /** @var \SureLv\Emails\Model\TypeUnsubscribeModel $typeUnsubscribeModel */

		$emailsListMember = $emailsListMemberModel->getListMemberById($memberId);
		if (!$emailsListMember) {
			throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException('Invalid unsubscribe link');
		}

		$contactId = $emailsListMember->getContactId();
		$listId = $emailsListMember->getListId();

		$onChangeMessage = null;

		// Change status to unsubscribed
		if ($emailsListMember->getStatus() === ListMemberStatus::SUBSCRIBED) {
			
			$listMemberStatusChangeDto = new ListMemberStatusChangeDto($memberId, $subType, ListMemberStatus::UNSUBSCRIBED, ListMemberStatus::SUBSCRIBED, $emailsListMember->getParams(), $contactId, $listId, $emailsListMember->getScopeType(), $emailsListMember->getScopeId() ?? 0);
			$listMemberStatusChangeDto->setListMember($emailsListMember);
			
			$eventPayload = [
				'list_member_id' => $memberId,
			];

			if (!$subType) {

				$emailsListMember
					->setStatus(ListMemberStatus::UNSUBSCRIBED)
					->setUnsubscribedAt(new \DateTime())
					;
				$emailsListMemberModel->update($emailsListMember, ['status', 'unsubscribed_at']);
				
				$emailEventService->register($messageId, EmailEventType::UNSUBSCRIBE, $eventPayload);
				$onChangeMessage = $emailEventService->getListMemberStatusChangeMessage($listMemberStatusChangeDto);

			} elseif (!$typeUnsubscribeModel->hasUnsubscribe($contactId, $emailsListMember->getScopeType(), $emailsListMember->getScopeId() ?? 0, $subType)) {

				$typeUnsubscribe = new TypeUnsubscribe();
				$typeUnsubscribe
					->setContactId($contactId)
					->setScopeType($emailsListMember->getScopeType())
					->setScopeId($emailsListMember->getScopeId() ?? 0)
					->setEmailType($subType)
					;
				$typeUnsubscribeModel->add($typeUnsubscribe);
				
				$emailEventService->register($messageId, EmailEventType::UNSUBSCRIBE, $eventPayload);
				$onChangeMessage = $emailEventService->getListMemberStatusChangeMessage($listMemberStatusChangeDto);

			}

		}

		if ($onChangeMessage) {
			$bus->dispatch($onChangeMessage);
		}

		if ($request->isMethod('POST')) {
            return new Response('OK', Response::HTTP_OK);
        }

        return new Response('<p>Unsubscribe successful</p>', Response::HTTP_OK);
	}

	public function trackClick(int $id, string $hash, ModelService $modelService): Response
	{
		$trackingModel = $modelService->getModel(TrackingModel::class); /** @var \SureLv\Emails\Model\TrackingModel $trackingModel */

		$dbTracking = $trackingModel->getById($id);
		if (!$dbTracking || $dbTracking->getHash() !== $hash) {
			return $this->returnInvalidResponseByType(EmailTrackingType::CLICK, 'Invalid tracking token');
		}

		$trackingModel->registerEvent($dbTracking);
		$url = $dbTracking->getContext()['url'] ?? '';
		if ($url) {
			return new RedirectResponse($url);
		}

		return $this->returnInvalidResponseByType(EmailTrackingType::CLICK, 'Invalid tracking context');
	}

	public function trackOpen(int $id, string $hash, ModelService $modelService): Response
	{
		$trackingModel = $modelService->getModel(TrackingModel::class); /** @var \SureLv\Emails\Model\TrackingModel $trackingModel */

		$dbTracking = $trackingModel->getById($id);
		if (!$dbTracking || $dbTracking->getHash() !== $hash) {
			return $this->returnInvalidResponseByType(EmailTrackingType::OPEN, 'Invalid tracking token');
		}

		$trackingModel->registerEvent($dbTracking);
		return $this->returnTrackingPixel();
	}


	/**
	 * 
	 * PRIVATE METHODS
	 * 
	 */


	/**
	 * Return invalid response by type
	 * 
	 * @param EmailTrackingType $type
	 * @param string $message
	 * @return Response
	 */
	private function returnInvalidResponseByType(EmailTrackingType $type, string $message = ''): Response
	{
		if ($type === EmailTrackingType::CLICK) {
			throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException($message);
		} elseif ($type === EmailTrackingType::OPEN) {
			return $this->returnTrackingPixel();
		}
		return new Response('', Response::HTTP_NOT_FOUND);
	}

	/**
	 * Return tracking pixel
	 * 
	 * @return Response
	 */
	private function returnTrackingPixel(): Response
    {
        // Base64 encoded 1x1 transparent GIF
        $pixel = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
        
        return new Response($pixel, 200, [
            'Content-Type' => 'image/gif',
            'Content-Length' => strlen($pixel),
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => 'Thu, 01 Jan 1970 00:00:00 GMT'
        ]);
    }

}