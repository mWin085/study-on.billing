<?php

namespace App\Command;

use App\Repository\CourseRepository;
use App\Repository\TransactionRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

#[AsCommand(name: 'payment:report')]
class PaymentRecordCommand extends Command
{
    const TYPE_NAMES = [
        1 => 'Аренда',
        2 => 'Покупка'
    ];
    private TransactionRepository $transactionRepository;
    private MailerInterface $mailer;

    public function __construct(TransactionRepository $transactionRepository, MailerInterface $mailer, private string $adminEmail)
    {
        parent::__construct();
        $this->transactionRepository = $transactionRepository;
        $this->mailer = $mailer;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $interval = new \DateInterval('P1M');
        $transactionsData = $this->transactionRepository->findAllByDate($interval);

        $groupedData = [];

        foreach ($transactionsData as $item) {
            $course = $item['coursecode'];
            if (!isset($groupedData[$course])) {
                $groupedData[$course] = [];
            }
            $groupedData[$course][] = $item;
        }
        unset($item);

        if (!empty($groupedData)) {
            $reportData = [];
            foreach ($groupedData as $course => $items) {
                $reportData[] = [
                    'courseName' => $items[0]['coursename'],
                    'summ' => array_sum(array_column($items, 'value')),
                    'type' => self::TYPE_NAMES[$items[0]['coursetype']],
                    'count' => count($items)
                ];
            }

            $email = (new TemplatedEmail())
                ->to(new Address($this->adminEmail))
                ->subject('Отчет по оплатам за месяц')
                ->htmlTemplate('mail/paymentReport.html.twig')
                ->context([
                    'title' => 'Отчет об оплаченных курсах за период ' . (new \DateTime())->sub($interval)->format('d.m.Y') . ' - ' . (new \DateTime())->format('d.m.Y'),
                    'reportData' => $reportData,
                    'total' => array_sum(array_column($reportData, 'summ'))
                ]);

                try {
                    $this->mailer->send($email);
                } catch (TransportExceptionInterface $e) {
                    // some error prevented the email sending; display an
                    // error message or try to resend the message
                }
        }

        return Command::SUCCESS;
    }


}