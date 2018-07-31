<?php

use Symfony\Component\Validator\Validation;

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 12/16/16
 * Time: 2:21 PM
 */
class SWMSetServiceDataDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_ACTION;

    /**
     * @param array $params
     * @return mixed
     */
    public function run(array $params)
    {
        $OrderModel = new OrderModel();
        $OrderModel->unserialize($params['orderModel']);

        $OrdersService = new OrdersServices();
        $OrdersService->unserialize($params['object']);

        // запишем аудит
        $OrdersServiceHistory = new OrdersServicesHistory();
        $OrdersServiceHistory->setOrderData($OrderModel);
        $OrdersServiceHistory->setObjectData($OrdersService);

        $validator = Validation::createValidatorBuilder()
            ->addMethodMapping('loadValidatorMetadata')
            ->getValidator();

        // дата начала
        if (!empty($params['orderServiceData']['dateStart'])) {
            $OrdersService->setDateStart($params['orderServiceData']['dateStart']);
        }

        // дата окончания
        if (!empty($params['orderServiceData']['dateFinish'])) {
            $OrdersService->setDateFinish($params['orderServiceData']['dateFinish']);
        }

        // цены
        try {
            if (!empty($params['orderServiceData']['salesTerms'])) {
                if (count($params['orderServiceData']['salesTerms'])) {
                    foreach ($params['orderServiceData']['salesTerms'] as $type => $salesTerm) {
                        // можно обновить только брутто цену клиента
                        if ($type == 'client') {
                            // проверим качество валюты
                            if (!empty($salesTerm['currency'])) {
                                $Currency = CurrencyStorage::findByString($salesTerm['currency']);

                                if (!$Currency) {
                                    throw new InvalidArgumentException('Валюта не найдена или не соответствует валюте продажи', OrdersErrors::INPUT_PARAMS_ERROR);
                                }

                                $brutto = $salesTerm['amountBrutto'];

                                $salesTerm['amountBrutto'] = CurrencyRates::getInstance()->calculateInCurrency($brutto, $salesTerm['currency'], $OrdersService->getSaleCurrency()->getCode());
                                $salesTerm['amountNetto'] = CurrencyRates::getInstance()->calculateInCurrency($brutto, $salesTerm['currency'], $OrdersService->getSaleCurrency()->getCode());
                                $salesTerm['currency'] = $OrdersService->getSaleCurrency()->getCode();
                            } else {
                                throw new InvalidArgumentException('Не указана валюта', OrdersErrors::INPUT_PARAMS_ERROR);
                            }

                            $OrdersService->setSaleTerm($type, $salesTerm);
                            break;
                        }
                    }
                } else {
                    $this->setError(OrdersErrors::INPUT_PARAMS_ERROR);
                    return;
                }
            }
        } catch (InvalidArgumentException $e) {
            $this->setError($e->getCode());
            $this->addLog($e->getMessage(), 'warning');
            return;
        } catch (DomainException $e) {
            $this->setError(OrdersErrors::ORDER_SERVICE_FATAL_ERROR);
            return;
        }

        // штрафы только для отелей
        try {
            // комиссия агента
            if (!empty($params['orderServiceData']['agencyProfit'])) {
                if ($OrderModel->getCompany()->isAgent()) {
                    $OrdersService->setAgencyProfit($params['orderServiceData']['agencyProfit']);
                } else {
                    $this->setError(OrdersErrors::COMMISSION_CHANGE_ONLY_FOR_AGENCIES);
                    return;
                }
            }

            if (!empty($params['orderServiceData']['cancelPenalties']) && is_array($params['orderServiceData']['cancelPenalties'])) {
                // сначала удалим все штрафы
                $offer = $OrdersService->getOffer();
                $offer->clearCancelPenalties();
                // запишем новые
                $offer->addCancelPenalty(['supplierCurrency' => $params['orderServiceData']['cancelPenalties']]);
            }
        } catch (InvalidArgumentException $e) {
            $this->setError($e->getCode());
            $this->addLog($e->getMessage(), 'warning');
            return;
        } catch (DomainException $e) {
            $this->setError(OrdersErrors::ORDER_SERVICE_FATAL_ERROR);
            return;
        }

        $violations = $validator->validate($OrdersService);

        if (count($violations) > 0) {
            foreach ($violations as $violation) {
                $this->setError($violation->getMessage());
                $OrdersServiceHistory->setCommentTpl('{{165}}');
                $OrdersServiceHistory->setActionResult(1);
            }
        }

        if (!$OrdersService->save()) {
            $OrdersServiceHistory->setCommentTpl('{{165}}');
            $OrdersServiceHistory->setActionResult(1);
            $this->setError(OrdersErrors::ORDER_SERVICE_FATAL_ERROR);
            return;
        }

        $this->params['object'] = $OrdersService->serialize();
        $this->addOrderAudit($OrdersServiceHistory);
    }
}