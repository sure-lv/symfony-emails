<?php

namespace SureLv\Emails\Command;

use SureLv\Emails\Enum\JobStatus;
use SureLv\Emails\Model\JobModel;
use SureLv\Emails\Service\ModelService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
	name: 'surelv:emails:queue-draft-job',
	description: 'Queue draft job',
)]
class QueueDraftJobCommand extends Command
{
    
    public function __construct(private ModelService $modelService)
	{
	}

    protected function configure(): void
    {
        $this->addOption('job-id', null, InputOption::VALUE_OPTIONAL, 'Job ID', 0);
        $this->addOption('list', null, InputOption::VALUE_OPTIONAL, 'Show list of draft jobs', 0);
    }

	protected function execute(InputInterface $in, OutputInterface $out): int
    {
        $jobModel = $this->modelService->getModel(JobModel::class); /** @var \SureLv\Emails\Model\JobModel $jobModel */

        // Show list of draft jobs
        $list = (bool)$in->getOption('list');
        if ($list) {
            $jobs = $jobModel->getDraftJobs();
            $out->writeln('<info>List of draft jobs:</info>');
            foreach ($jobs as $job) {
                $out->writeln('<info>' . $job->getId() . ' - ' . $job->getName() . '</info>');
            }
            return self::SUCCESS;
        }

        $jobId = (int)$in->getOption('job-id');
        $job = $jobModel->getById($jobId);
        if (!$job) {
            $out->writeln('<error>Job not found</error>');
            return Command::FAILURE;
        }
        if ($job->getStatus() !== JobStatus::DRAFT) {
            $out->writeln('<error>Job is not a draft</error>');
            return Command::FAILURE;
        }

        $jobModel->updateStatus($job, JobStatus::QUEUED);
        $out->writeln('<info>Job queued successfully</info>');

        return self::SUCCESS;
    }

}