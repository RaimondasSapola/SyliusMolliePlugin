<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * another great project.
 * You can find more information about us on https://bitbag.shop and write us
 * an email on mikolaj.krol@bitbag.pl.
 */

declare(strict_types=1);

namespace BitBag\SyliusMolliePlugin\Processor;

use BitBag\SyliusMolliePlugin\Entity\MollieGatewayConfig;
use BitBag\SyliusMolliePlugin\PaymentFee\Calculate;
use Payum\Core\Model\PaymentInterface;
use Sylius\Component\Order\Model\OrderInterface;
use Sylius\Component\Payment\Model\PaymentMethodInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

final class PaymentSurchargeProcessor implements PaymentSurchargeProcessorInterface
{
    /** @var Calculate */
    private $calculate;

    /** @var SessionInterface */
    private $session;

    public function __construct(Calculate $calculate, SessionInterface $session)
    {
        $this->calculate = $calculate;
        $this->session = $session;
    }

    public function process(OrderInterface $order): void
    {
        /** @var PaymentInterface $payment */
        $payment = $order->getPayments()->first();

        /** @var PaymentMethodInterface $paymentMethod */
        $paymentMethod = $payment->getMethod();

        if ($paymentMethod->getGatewayConfig()->getFactoryName() !== 'mollie') {
            return;
        }

        $data = $this->session->get('mollie_payment_options', null);
        $molliePaymentMethod = $data['molliePaymentMethods'];

        $paymentSurcharge = $this->getMolliePaymentSurcharge($paymentMethod, $molliePaymentMethod);

        if (null === $paymentSurcharge) {
            return;
        }

        $this->calculate->calculateFromCart($order, $paymentSurcharge);
    }

    private function getMolliePaymentSurcharge(PaymentMethodInterface $paymentMethod, string $molliePaymentMethod): ?MollieGatewayConfig
    {
        $configMethods = $paymentMethod->getGatewayConfig()->getMollieGatewayConfig();
        foreach ($configMethods as $configMethod) {
            /** @var MollieGatewayConfig $configMethod */
            if ($configMethod->getMethodId() === $molliePaymentMethod) {
                return $configMethod;
            }
        }

        return null;
    }
}
