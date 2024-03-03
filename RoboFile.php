<?php

declare(strict_types = 1);

use Consolidation\AnnotatedCommand\Attributes as Cli;
use NuvoleWeb\Robo\Task\Config\Robo\loadTasks as ConfigLoader;
use Pamald\Robo\Pamald\PamaldTaskLoader;
use Pamald\Robo\Pamald\PrepareCommitMsgTrait;
use Pamald\Robo\PamaldComposer\PamaldComposerTaskLoader;
use Pamald\Robo\PamaldNpm\PamaldNpmTaskLoader;
use Pamald\Robo\PamaldYarn\PamaldYarnTaskLoader;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Robo\Collection\CollectionBuilder;
use Robo\Common\ConfigAwareTrait;
use Robo\Contract\ConfigAwareInterface;
use Robo\Tasks;

class RoboFile extends Tasks implements LoggerAwareInterface, ConfigAwareInterface
{
    use LoggerAwareTrait;
    use ConfigAwareTrait;
    use ConfigLoader;
    use PamaldTaskLoader;
    use PamaldComposerTaskLoader;
    use PamaldNpmTaskLoader;
    use PamaldYarnTaskLoader;
    use PrepareCommitMsgTrait;

    #[Cli\Command(
        name: 'githook:prepare-commit-msg',
    )]
    #[Cli\Help(
        description: 'Git hook callback command for "./.git/hooks/prepare-commit-msg".',
        synopsis: <<< 'TEXT'
            If package "sweetchuck/git-hooks" does its job, then this command will be called automatically by Git.
            That is why this command is hidden.
            Check your Git configuration:

            ```shell
            git config core.hooksPath
            ```

            Output should be something like this:
            ```plain
            ./vendor/sweetchuck/git-hooks/git-hooks/bash
            ```

            For more information visit: https://git-scm.com/docs/githooks#_prepare_commit_msg
            TEXT,
        hidden: true,
    )]
    #[Cli\Argument(
        name: 'commitMsgFilePath',
        description: 'The name of the file that contains the commit log message.',
    )]
    #[Cli\Argument(
        name: 'messageSource',
        description: 'The source of the commit message. message|template|merge|squash|commit.',
        suggestedValues: [
            'message',
            'template',
            'merge',
            'squash',
            'commit',
        ],
    )]
    #[Cli\Argument(
        name: 'sha1',
        description: 'Documentation @todo.',
    )]
    public function cmdPrepareCommitMsgExecute(
        string $commitMsgFilePath,
        string $messageSource = '',
        string $sha1 = '',
    ): CollectionBuilder {
        $sha1 = $sha1 ?: 'HEAD';
        $anp = '';
        $ans = '';
        $cb = $this->collectionBuilder();
        $cb->addTaskList(
            $this->getGitHookPrepareCommitMsgTaskList(
                $cb,
                $commitMsgFilePath,
                $messageSource,
                $sha1,
                $anp,
                $ans,
            ),
        );

        $taskComposerModifier = $this
            ->taskPamaldComposerModifyCommitMsgParts()
            ->setStateKeyCommitMsgParts("{$anp}commitMsg.parts{$ans}")
            ->setSha1($sha1);
        $cb->getCollection()->after(
            'file.parse',
            $taskComposerModifier,
            'pamald.composer.modify-commit-msg-parts',
        );

        $taskNpmModifier = $this
            ->taskPamaldNpmModifyCommitMsgParts()
            ->setStateKeyCommitMsgParts("{$anp}commitMsg.parts{$ans}")
            ->setSha1($sha1);
        $cb->getCollection()->after(
            'file.parse',
            $taskNpmModifier,
            'pamald.npm.modify-commit-msg-parts',
        );

        $taskYarnModifier = $this
            ->taskPamaldYarnModifyCommitMsgParts()
            ->setStateKeyCommitMsgParts("{$anp}commitMsg.parts{$ans}")
            ->setSha1($sha1);
        $cb->getCollection()->after(
            'file.parse',
            $taskYarnModifier,
            'pamald.yarn.modify-commit-msg-parts',
        );

        return $cb;
    }
}
