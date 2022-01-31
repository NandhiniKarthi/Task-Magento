<?php
 
namespace Inchoo\CustomerCreation\Model;
 
use Exception;
use Generator;
use Magento\Framework\Filesystem\Io\File;
use Magento\Store\Model\StoreManagerInterface;
use Inchoo\CustomerCreation\Model\Import\CustomerImport;
use Symfony\Component\Console\Output\OutputInterface;
 
class Customer
{
    
private $file;
private $storeManagerInterface;
private $customerImport;
private $output;
 
public function __construct(
    File $file,
    StoreManagerInterface $storeManagerInterface,
    CustomerImport $customerImport
) {
    $this->file = $file;
    $this->storeManagerInterface = $storeManagerInterface;
    $this->customerImport = $customerImport;
}
public function install(string $fixture, OutputInterface $output): void
{
    $this->output = $output;
 
    // get store and website ID
    $store = $this->storeManagerInterface->getStore();
    $websiteId = (int) $this->storeManagerInterface->getWebsite()->getId();
    $storeId = (int) $store->getId();
 
    // read the csv header
    $header = $this->readCsvHeader($fixture)->current();
 
    // read the csv file and skip the first (header) row
    $row = $this->readCsvRows($fixture, $header);
    $row->next();
 
    // while the generator is open, read current row data, create a customer and resume the generator
    while ($row->valid()) {
        $data = $row->current();
        $this->createCustomer($data, $websiteId, $storeId);
        $row->next();
    }
}
private function readCsvRows(string $file, array $header): ?Generator
{
    $handle = fopen($file, 'rb');
 
    while (!feof($handle)) {
        $data = [];
        $rowData = fgetcsv($handle);
        if ($rowData) {
            foreach ($rowData as $key => $value) {
                $data[$header[$key]] = $value;
            }
            yield $data;
        }
    }
 
    fclose($handle);
}
 
private function readCsvHeader(string $file): ?Generator
{
    $handle = fopen($file, 'rb');
 
    while (!feof($handle)) {
        yield fgetcsv($handle);
    }
 
    fclose($handle);
}
public function datainstall(string $fixture, OutputInterface $output): void
{
    $this->output = $output;
 
    // get store and website ID
    $store = $this->storeManagerInterface->getStore();
    $websiteId = (int) $this->storeManagerInterface->getWebsite()->getId();
    $storeId = (int) $store->getId();
    //read json file
    $file = file_get_contents($fixture);
    $dataarray = json_decode($file,true);
    foreach($dataarray as $data )
    {
        $this->createCustomer($data,$websiteId,$storeId);
    }
    
    }
private function createCustomer(array $data, int $websiteId, int $storeId): void
{
  try {
      // collect the customer data
      $customerData = [
          'email'         => $data['email'],
          '_website'      => 'base',
          '_store'        => 'default',
          'confirmation'  => null,
          'firstname'     => $data['firstname'],
           'lastname'      => $data['lastname'],
          'store_id'      => $storeId,
          'website_id'    => $websiteId,
          
          'disable_auto_group_change' => 0,
          'some_custom_attribute'     => 'some_custom_attribute_value'
       ];
 
      // save the customer data
      $this->customerImport->importCustomerData($customerData);
  } catch (Exception $e) {
      $this->output->writeln(
          '<error>'. $e->getMessage() .'</error>',
          OutputInterface::OUTPUT_NORMAL
      );
  }
}
} 
