<?php

namespace App\Command;

use App\Repository\TransactionRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

#[AsCommand(name: 'payment:ending:notification')]
class PaymentEndingCommand extends Command
{
    private TransactionRepository $transactionRepository;
    private MailerInterface $mailer;

    public function __construct(TransactionRepository $transactionRepository, MailerInterface $mailer)
    {
        parent::__construct();
        $this->transactionRepository = $transactionRepository;
        $this->mailer = $mailer;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $transactionsData = $this->transactionRepository->findRentByDate(new \DateInterval('P1D'));

        $groupedData = [];

        foreach ($transactionsData as $item) {
            $email = $item['email'];
            if (!isset($groupedData[$email])) {
                $groupedData[$email] = [];
            }
            $groupedData[$email][] = $item;
        }
        unset($item);


        if (!empty($groupedData)) {
            foreach ($groupedData as $k => $items) {


                $text = '';
                foreach ($items as $item) {
                    $text .= $item['coursename'] . ' действует до ' . $item['validAt']->format("d.m.Y H:i") . "\n";
                }
                $email = (new TemplatedEmail())
                    ->from('study-on@example.com')
                    ->to(new Address($k))
                    ->subject('Срок аренды подходит к концу:')
                    ->htmlTemplate('mail/email.html.twig')
                    ->context([
                        'title' => 'Уважаемый клиент! У вас есть курсы, срок аренды которых подходит к концу: ',
                        'text' => $text,
                    ]);

                try {
                    $this->mailer->send($email);
                } catch (TransportExceptionInterface $e) {
                    // some error prevented the email sending; display an
                    // error message or try to resend the message
                }
            }
        }


        return Command::SUCCESS;
    }
}