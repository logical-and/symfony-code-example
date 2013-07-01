<?php

namespace AdvUa\BillingBundle\Payment;

use AdvUa\BillingBundle\Entity\InvoiceDetail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class W1Payment extends AbstractPayment {

	protected function doGenerateForm(InvoiceDetail $orderDetail, $responseUri, $returnUri)
	{
		$fields = array(
			'WMI_MERCHANT_ID'     => $orderDetail->getUserToPay()->getWallet(),
			'WMI_PAYMENT_AMOUNT'  => 1, // $orderDetail->getAmount(),
			'WMI_CURRENCY_ID'     => 840,
			'WMI_DESCRIPTION'     => $this->getTranslator()->trans('Оплата счёта на кошелек') . ': ' . $orderDetail->getUserToPay()->getWallet(),
			'WMI_PAYMENT_NO'      => $orderDetail->getId(),
			'WMI_RECIPIENT_LOGIN' => $orderDetail->getInvoice()->getUser()->getWallet(),
			'WMI_SUCCESS_URL'     => $returnUri,
			'WMI_FAIL_URL'        => $returnUri
		);

		// Signature
		if ($orderDetail->getUserToPay()->getWalletCode())
		{
			$fields[ 'WMI_SIGNATURE' ] = $this->generateSignature($fields, $orderDetail->getUserToPay()->getWalletCode());
		}

		return $this->renderView('BillingBundle:PaymentService:w1payment.html.twig', array(
			'fields' => $fields
		));
	}

	protected function doFetchResponseRequest(Request $request)
	{
		// Log result
		$this->getLogger()->info(json_encode($request->request->all()));

		if (! $request->get('WMI_ORDER_STATE')) throw new NotFoundHttpException();

		// Check signature here, if you want? As long as fetcher is secret - check is senselessly
		if ('OK' == $request->get('WMI_ORDER_STATE'))
		{
			try {
				$this->updateInvoice($request->get('WMI_PAYMENT_NO'), InvoiceDetail::STATUS_PAID);
			}
			catch(NotFoundHttpException $e)
			{
				return 'WMI_RESULT=RETRY&WMI_DESCRIPTION=' . urlencode($e->getMessage());
			}
		}

		return 'WMI_RESULT=OK';
	}

	protected function generateSignature(array $fields, $key)
	{
		// source: http://merchant.w1.ru/checkout/site/develope/

		//Сортировка значений внутри полей
		foreach ($fields as $name => $val)
		{
			if (is_array($val))
			{
				usort($val, "strcasecmp");
				$fields[ $name ] = $val;
			}
		}


		// Формирование сообщения, путем объединения значений формы,
		// отсортированных по именам ключей в порядке возрастания.
		uksort($fields, "strcasecmp");
		$fieldValues = "";

		foreach ($fields as $value)
		{
			if (is_array($value))
				foreach ($value as $v)
				{
					//Конвертация из текущей кодировки (UTF-8)
					//необходима только если кодировка магазина отлична от Windows-1251
					$v = iconv("utf-8", "windows-1251", $v);
					$fieldValues .= $v;
				}
			else
			{
				//Конвертация из текущей кодировки (UTF-8)
				//необходима только если кодировка магазина отлична от Windows-1251
				$value = iconv("utf-8", "windows-1251", $value);
				$fieldValues .= $value;
			}
		}

		// Формирование значения параметра WMI_SIGNATURE, путем
		// вычисления отпечатка, сформированного выше сообщения,
		// по алгоритму MD5 и представление его в Base64

		$signature = base64_encode(pack("H*", md5($fieldValues . $key)));

		return $signature;
	}
}