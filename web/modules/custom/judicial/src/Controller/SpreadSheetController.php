<?php

namespace Drupal\judicial\Controller;

use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Controller\ControllerBase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;

class SpreadSheetController extends ControllerBase {

  public function downloadXls() {
    $response = new Response();

    $filename = sprintf('thesaurus-%s.xls', date('Ymd-Hi'));

    $response->headers->set('Pragma', 'no-cache');
    $response->headers->set('Expires', '0');
    $response->headers->set('Content-Type', 'application/vnd.ms-excel');
    $response->headers->set('Content-Disposition', 'attachment; filename=' . $filename . ';');

    // Retrieve the glossary terms
    $vid = 'thesaurus';
    $all_terms =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid);

    // Generate and output code will be inserted here.
    $spreadsheet = new Spreadsheet();

    //Set metadata.
    $spreadsheet->getProperties()->setCreator('InforMEA Initiative');
    $spreadsheet->getProperties()->setTitle('LEO - Law and Environment Ontology');

    // Get the active sheet.
    $spreadsheet->setActiveSheetIndex(0);

    //Rename sheet
    $spreadsheet->getActiveSheet()->setTitle('Concepts');

    $cols = array(
      'A1' => 'ID',
      'B1' => 'Term',
      'C1' => 'Topic #1',
      'D1' => 'Topic #2',
      'E1' => 'Topic #3',
      'F1' => 'Topic #4',
      'G1' => 'Synonym #1',
      'H1' => 'Synonym #2',
      'I1' => 'Synonym #3',
      'J1' => 'Synonym #4',
      'K1' => 'Synonym #5',
      'L1' => 'Synonym #6',
      'M1' => 'Synonym #7',
      'N1' => 'Synonym #8',
      'O1' => 'Synonym #9',
      'P1' => 'Synonym #10',
      'Q1' => 'Synonym #11',
      'R1' => 'Synonym #12',
      'S1' => 'Definition #1',
      'T1' => 'Definition #2',
      'U1' => 'Definition #3',
      'V1' => 'Definition #4',
    );
    foreach($cols as $cellId => $title) {
      $spreadsheet->getActiveSheet()->SetCellValue($cellId, $title);
      $spreadsheet->getActiveSheet()->getStyle($cellId)->getFont()->setBold(true);
    }

    $i = 2;
    $unique = array();
    foreach($all_terms as $term_sk) {
      if (in_array($term_sk->tid, $unique)) {
        continue;
      }
      $tid = $term_sk->tid;
      $term_ob = \Drupal\taxonomy\Entity\Term::load($tid);
      $cell_name = 'A' . $i;

      // Topics
      $topics = ['', '', '', ''];
      $j = 0;
      foreach($term_ob->field_topics as $value) {
        $topics[$j] = $value->entity->label();
        $j++;
      }

      // Synonyms
      $syn = ['', '', '', '', '', '', '', '', '', '', '', ''];
      $j = 0;
      foreach($term_ob->field_synonyms as $value) {
        if (!empty($value)) {
          $syn[$j] = $value->value;
        }
        $j++;
      }

      // Definitions
      $definitions = ['', '', '', ''];
      $j = 0;
      foreach($term_ob->field_definitions as $value) {
        if (!empty($value)) {
          $definitions[$j] = $value->value;
        }
        $j++;
      }

      $row = [
        $tid,
        $term_ob->getName(),
        $topics[0],
        $topics[1],
        $topics[2],
        $topics[3],
        $syn[0],
        $syn[1],
        $syn[2],
        $syn[3],
        $syn[4],
        $syn[5],
        $syn[6],
        $syn[7],
        $syn[8],
        $syn[9],
        $syn[10],
        $syn[11],
        $definitions[0],
        $definitions[1],
        $definitions[2],
        $definitions[3],
      ];
      $spreadsheet->getActiveSheet()->fromArray($row, NULL, $cell_name);
      $i++;
    }
    $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
    ob_start();
    $writer->save('php://output');
    $content = ob_get_clean();

    // Memory cleanup.
    $spreadsheet->disconnectWorksheets();
    unset($spreadsheet);

    // Return the response
    $response->setContent($content);
    return $response;
  }
}
