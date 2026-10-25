<?php
declare(strict_types=1);
namespace MRBS;

// Runs git with the given arguments and returns the first line of its output, or null on any
// failure.  Stderr is captured and discarded so that, for example, a shell's "git: not found"
// is not printed on the console.
function run_git(string $git_command, array $args) : ?string
{
  if (!function_exists('proc_open') || function_disabled('proc_open'))
  {
    return null;
  }

  $command = array_merge([$git_command], $args);

  // Before PHP 7.4 proc_open() only accepts a string, which is run by a shell
  if (PHP_VERSION_ID < 70400)
  {
    $command = implode(' ', array_map('escapeshellarg', $command));
  }

  $process = @proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);

  if (!is_resource($process))
  {
    return null;
  }

  fclose($pipes[0]);
  $stdout = stream_get_contents($pipes[1]);
  stream_get_contents($pipes[2]);
  fclose($pipes[1]);
  fclose($pipes[2]);

  if ((proc_close($process) !== 0) || ($stdout === false))
  {
    return null;
  }

  $result = strtok(trim($stdout), "\r\n");

  return (is_string($result) && ($result !== '')) ? $result : null;
}


// This should probably be a class, but you can only have protected
// class members in PHP 5, so we won't bother

function get_mrbs_version() : string
{
  // Read the actual version number from a much simpler file
  require MRBS_ROOT . '/version_num.inc';

  global $git_command;

  $mrbs_version = "MRBS $mrbs_version_number";

  // We are only interested in success, so any failure just leaves out the git details
  $branch = run_git($git_command, ['-C', MRBS_ROOT, 'rev-parse', '--abbrev-ref', 'HEAD']);

  if (isset($branch))
  {
    $commit = run_git($git_command, ['-C', MRBS_ROOT, 'rev-parse', '--short', 'HEAD']);

    if (isset($commit))
    {
      $mrbs_version .= "+git ($branch $commit)";
    }
  }

  return $mrbs_version;
}
