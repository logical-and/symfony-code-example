<?php

namespace AdvUa\BillingBundle\Payment;

use AdvUa\BillingBundle\BillingEvents;
use AdvUa\BillingBundle\BillingMailer;
use AdvUa\BillingBundle\Entity\InvoiceDetail;
use AdvUa\BillingBundle\Entity\InvoiceDetailRepository;
use AdvUa\BillingBundle\Event\Dispatcher;
use AdvUa\BillingBundle\Event\InvoiceDetailEvent;
use Doctrine\Bundle\DoctrineBundle\Registry;
use ErrorException;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

abstract class AbstractPayment implements PaymentInterface {

	private $container;

	public function __construct(ContainerInterface $container)
	{
		$this->container = $container;
	}

	/**
	 * Returns a rendered view.
	 *
	 * @param string $view       The view name
	 * @param array $parameters An array of parameters to pass to the view
	 * @return string The rendered view
	 */
	protected function renderView($view, array $parameters = array())
	{
		return $this->container->get('templating')->render($view, $parameters);
	}

	/**
	 * Shortcut to return the Doctrine Registry service.
	 *
	 * @return Registry
	 * @throws \LogicException If DoctrineBundle is not available
	 */
	protected function getDoctrine()
	{
		if (! $this->container->has('doctrine'))
		{
			throw new \LogicException('The DoctrineBundle is not registered in your application.');
		}

		return $this->container->get('doctrine');
	}

	/**
	 * @return Dispatcher
	 */
	protected function getBillingDispatcher()
	{
		return $this->container->get('billing.event_dispatcher');
	}

	/**
	 * @return Translator
	 */
	protected function getTranslator()
	{
		return $this->container->get('translator');
	}

	protected function decideReturnUri($returnUri)
	{
		return $returnUri ? $this->completeUri($returnUri) : $this->getRequest()->getUri();
	}

	protected function completeUri($uri)
	{
		if (FALSE === strpos($uri, 'http')) $uri = $this->getRequest()->getUriForPath(
			str_replace($this->getRequest()->getBaseUrl(), '', $uri));

		return $uri;
	}

	/**
	 * Shortcut to return the request service.
	 *
	 * @return Request
	 */
	protected function getRequest()
	{
		return $this->container->get('request');
	}

	/**
	 * @return BillingMailer
	 */
	protected function getMailer()
	{
		return $this->container->get('billing.mailer');
	}

	/**
	 * @return Logger
	 *
	 */
	protected function getLogger()
	{
		return $this->container->get('logger');
	}

	/**
	 * @param $invoiceDetailId
	 * @param $invoiceStatus
	 * @throws NotFoundHttpException
	 */
	protected function updateInvoice($invoiceDetailId, $invoiceStatus)
	{
		if (! $invoiceDetailId) throw new NotFoundHttpException('Invoice not found!');

		$invoiceDetail = InvoiceDetailRepository::factory($this->getDoctrine()->getManager())
			->findById($invoiceDetailId);

		if (! $invoiceDetail) throw new NotFoundHttpException('Invoice not found!');


		$invoiceDetail->setStatus($invoiceStatus);

		$em = $this->getDoctrine()->getManager();
		$em->persist($invoiceDetail);
		$em->flush();

		$this->getBillingDispatcher()->dispatch(BillingEvents::payment,
			new InvoiceDetailEvent($invoiceDetail, $invoiceDetail->getInvoice()->getUser())
		);
	}

	/**
	 * @param InvoiceDetail $orderDetail
	 * @param string $responseUri
	 * @param string $returnUri
	 * @return mixed
	 */
	public function generateForm(InvoiceDetail $orderDetail, $responseUri, $returnUri = NULL)
	{
		return $this->doGenerateForm($orderDetail, $this->completeUri($responseUri), $this->decideReturnUri($returnUri));
	}

	/**
	 * @param Request $request
	 * @throws ErrorException
	 * @return mixed
	 */
	public function fetchResponseRequest(Request $request)
	{
		$response = $this->doFetchResponseRequest($request);

		if (! $response) $response = new Response('Yep!');
		else if (is_string($response)) $response = new Response($response);
		else if (! $response instanceof Response) throw new ErrorException('Response type unknown, string | Response | null expected');

		return $response;
	}

	abstract protected function doGenerateForm(InvoiceDetail $orderDetail, $responseUri, $returnUri);

	abstract protected function doFetchResponseRequest(Request $request);
}