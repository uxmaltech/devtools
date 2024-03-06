<?php

namespace Uxmal\Devtools\Command\GitHub;

use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Console\Command;
use GuzzleHttp\Client;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class CreatePullRequestCommand extends Command
{
    protected $signature = 'github:create-pull-request {title}';
    protected $description = 'Create a GitHub pull request for multiple repositories with the current branch against main, skip if up to date.';

    /**
     * @throws GuzzleException
     * @throws \Exception
     */
    public function handle(): void
    {
        $repositories = config('uxmaltech.git.repositories');
        $title = $this->argument('title');
        if( is_file('./'.$title.'.message')){
            $body = file_get_contents('./'.$title.'.message');
        } else {
            $body = $this->ask('Please enter the pull request description');
        }

        dump($body);


        foreach ($repositories as $repository => $repositoryPath) {
            // Assume $repositoryPath is the path to the repository directory
            $repositoryPath = realpath($repositoryPath);

            // Check if the repository path exists
            if (!is_dir($repositoryPath)) {
                $this->error("The directory for repository '{$repository}' does not exist.");
                continue;
            }

            // Get the current branch name
            $process = new Process(['git', 'rev-parse', '--abbrev-ref', 'HEAD'], $repositoryPath);
            $process->run();

            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            $head = trim($process->getOutput());
            $base = 'main';

            // Check for differences between the current branch and the base branch
            $diffProcess = new Process(['git', 'diff', '--quiet', $base, $head], $repositoryPath);
            $diffProcess->run();

            // If the exit code is 0, there are no differences
            if ($diffProcess->isSuccessful()) {
                $this->info("No changes detected for '$repository' between branches '$head' and '$base'. Skipping pull request creation.");
                continue;
                $deleteProcess = new Process(['git', 'branch', '-d', $head], $repositoryPath);
                $deleteProcess->run();
            }

            $this->info("Creating pull request for '$repository' from '$head' to '$base'...");
            continue;
            $client = new Client();
            $response = $client->post("https://api.github.com/repos/$repository/pulls", [
                'headers' => [
                    'Authorization' => 'token YOUR_GITHUB_TOKEN',
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'title' => $title,
                    'head' => $head,
                    'base' => $base,
                    'body' => $body,
                ],
            ]);

            if ($response->getStatusCode() == 201) {
                $this->info("Pull request created successfully for $repository from $head to $base!");
            } else {
                $this->error("Failed to create the pull request for $repository.");
            }
        }

    }
}