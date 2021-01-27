<?php

declare(strict_types=1);

namespace theodorejb\PhpStv;

class Utils
{
    public static function encodeHtml(string $text): string
    {
        return htmlspecialchars($text, ENT_COMPAT | ENT_HTML5);
    }

    /**
     * @param Ballot[] $ballots
     */
    public static function getBallotsHtml(array $ballots, string $title, bool $rename = false): string
    {
        $count = count($ballots);
        $index = 0;
        $html = <<<ballotHtml

        <h3>{$count} {$title}</h3>
        <table class="table">
          <thead>
            <tr>
              <th scope="col">Name</th>
              <th scope="col">Ranked choices</th>
            </tr>
          </thead>
          <tbody>

        ballotHtml;

        foreach ($ballots as $ballot) {
            $index++;
            $name = $rename ? "Vote #{$index}" : self::encodeHtml($ballot->name);
            $choices = self::encodeHtml(implode(",  ", $ballot->rankedChoices));

            $html .= <<<row
                <tr>
                  <td>{$name}</td>
                  <td>{$choices}</td>
                </tr>

            row;
        }

        $html .= "  </tbody>\n</table>\n";
        return $html;
    }
}
