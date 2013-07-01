<?php

namespace AdvUa\BillingBundle\Payment;

use AdvUa\BillingBundle\BillingEvents;
use AdvUa\BillingBundle\Entity\InvoiceDetail;
use AdvUa\BillingBundle\Entity\InvoiceDetailRepository;
use AdvUa\BillingBundle\Event\InvoiceDetailEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DummyPayment extends AbstractPayment {

	const TEMPLATE_PATH = 'BillingBundle:PaymentService:dummy.html.twig';

	/**
	 * @param InvoiceDetail $invoiceDetail
	 * @param string $responseUri
	 * @param string $returnUri
	 * @return mixed
	 */
	protected function doGenerateForm(InvoiceDetail $invoiceDetail, $responseUri, $returnUri)
	{
		return $this->renderView(self::TEMPLATE_PATH, array(
			'responseUri'   => $responseUri,
			'returnUri'     => $returnUri,
			'invoiceDetail' => $invoiceDetail
		));
	}

	/**
	 * @param Request $request
	 * @throws \Exception
	 * @return mixed
	 */
	protected function doFetchResponseRequest(Request $request)
	{
		$invoiceDetailId = $request->get('invoice_detail_id');

		$statuses = array('fail', 'win');
		$status   = $statuses[ array_rand($statuses) ];

		if ('win' == $status)
		{
			$this->updateInvoice($invoiceDetailId, InvoiceDetail::STATUS_PAID);

		} else if ('fail' == $status)
		{
			$this->updateInvoice($invoiceDetailId, InvoiceDetail::STATUS_FAILURE);
		}
	}
}