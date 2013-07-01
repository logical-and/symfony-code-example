<?php

namespace AdvUa\BillingBundle\Payment;

use AdvUa\BillingBundle\Entity\InvoiceDetail;
use Symfony\Component\HttpFoundation\Request;

interface PaymentInterface {

	/**
	 * @param Request $request
	 * @return mixed
	 */
	public function fetchResponseRequest(Request $request);

	/**
	 * @param InvoiceDetail $orderDetail
	 * @param string $responseUri
	 * @param string $returnUri
	 * @return mixed
	 */
	public function generateForm(InvoiceDetail $orderDetail, $responseUri, $returnUri = NULL);
}