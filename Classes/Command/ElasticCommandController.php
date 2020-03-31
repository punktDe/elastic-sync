<?php
declare(strict_types=1);

namespace PunktDe\Elastic\Sync\Command;

/*
 * This file is part of the PunktDe.Elastic.Clone package.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Cli\Exception\StopCommandException;
use Neos\Flow\Http\Client\CurlEngineException;
use Neos\Flow\Http\Exception;
use PunktDe\Elastic\Sync\Configuration\ConfigurationService;
use PunktDe\Elastic\Sync\Exception\ConfigurationException;
use PunktDe\Elastic\Sync\Exception\HttpException;
use PunktDe\Elastic\Sync\Exception\SynchronizationException;
use PunktDe\Elastic\Sync\Service\ElasticsearchService;
use PunktDe\Elastic\Sync\Synchronizer;

/**
 * @Flow\Scope("singleton")
 */
class ElasticCommandController extends CommandController
{
    /**
     * @Flow\InjectConfiguration(path="elasticDumpPath")
     * @var string
     */
    protected $elasticDumpPath;

    /**
     * @Flow\Inject
     * @var ElasticsearchService
     */
    protected $elasticSearchService;

    /**
     * @Flow\Inject
     * @var ConfigurationService
     */
    protected $configurationService;

    /**
     * @Flow\Inject
     * @var Synchronizer
     */
    protected $synchronizer;

    /**
     * @param string $preset
     * @param bool $yes
     * @return void
     * @throws StopCommandException
     * @throws CurlEngineException
     * @throws Exception
     * @throws SynchronizationException
     */
    public function syncCommand(string $preset, $yes = false): void
    {
        try {
        $this->runSynchronisation($preset, $yes);
        } catch (ConfigurationException $exception) {
            $this->outputLine('<error>Configuration Error: %s (%s)</error>', [$exception->getMessage(), $exception->getCode()]);
            $this->sendAndExit(1);
        }
    }

    private function checkIfElasticDumpExists(): void
    {
        if (!is_file($this->elasticDumpPath) || !is_executable($this->elasticDumpPath)) {
            $this->outputLine('<error>Error:</error> The elastic-dump script could not be found in %s', [$this->elasticDumpPath]);
            $this->outputLine('Please run <i>(cd %s && npm install)</i> (including parenthesis) or define the correct path.', [str_replace('node_modules/.bin/elasticdump', '', $this->elasticDumpPath)]);
            $this->quit(1);
        }
    }

    /**
     * @param string $preset
     * @param bool $yes
     * @throws StopCommandException
     * @throws CurlEngineException
     * @throws Exception
     * @throws SynchronizationException
     * @throws ConfigurationException
     */
    private function runSynchronisation(string $preset, bool $yes): void
    {
        $this->checkIfElasticDumpExists();
        $localConfiguration = $this->configurationService->getLocalConfiguration($preset);

        if (!$yes && $this->output->askConfirmation(sprintf('Would you really like to remove and replace the index <b>%s</b> (y/n)?', implode(',', array_map(static function ($index) {
                return $index['indexName'];
            }, $localConfiguration->getIndices()))), false) !== true) {
            $this->quit(1);
        }

        foreach ($localConfiguration->getIndices() as $index) {
            $this->output(sprintf('Removing index %s .... ', $index['indexName']));

            try {
                $this->elasticSearchService->deleteIndex($localConfiguration, $index['indexName']);
                $this->outputLine('<success>done</success>');
            } catch (HttpException $exception) {
                $this->outputLine(sprintf('<error>%s</error>', $exception->getMessage()));
                $this->quit(1);
            }
        }

        $this->synchronizer->sync($preset);

        $this->outputLine('<success>done</success>');
    }
}
