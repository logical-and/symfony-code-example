<?php

namespace AdvUa\BillingBundle\Payment;

use AdvUa\BillingBundle\Entity\InvoiceDetail;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Kernel;

class PaymentService {

	/**
	 * @var ContainerInterface
	 */
	private $container;

	public function __construct(Kernel $kernel)
	{
		$this->container = $kernel->getContainer();
	}

	public function getPaymentFor(InvoiceDetail $invoiceDetail = NULL)
	{
		return new W1Payment($this->container);
	}

	public function getPaymentForCallback(Request $request)
	{
		return new W1Payment($this->container);
	}
}