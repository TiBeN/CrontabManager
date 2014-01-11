<?php
/**
 * CrontabRepository class PHPUnit test cases
 * @author FranceProNet
 */
require_once dirname(__FILE__) . '/../../CrontabManager/CrontabRepository.php';
	
class CrontabRepositoryTest extends PHPUnit_Framework_TestCase {
	
	protected $fixturesPath;
	
	protected function setUp() {
		$this->fixturesPath = dirname(__FILE__) . '/Fixtures/';
	}	
	
	/**
	 * Test if the crontab file is parsed.
	 */
	public function testParseCrontab() {

		/* Create fake crontabAdapter */
		$fakeCrontabAdapter = $this->getMock('CrontabAdapter');
		$fakeCrontabAdapter
			->expects($this->any())
			->method('readCrontab')
			->will($this->returnValue(file_get_contents($this->fixturesPath . 'testing_read_crontab.txt')))
		;		
		
		/* Create expected crontabJobs */
		
		$expectedCrontabJob1 = new CrontabJob();
		$expectedCrontabJob1->minutes = '30';
		$expectedCrontabJob1->hours = '23';
		$expectedCrontabJob1->dayOfMonth = '*';
		$expectedCrontabJob1->months = '*';
		$expectedCrontabJob1->dayOfWeek = '*';
		$expectedCrontabJob1->taskCommandLine = 'df >> /tmp/df.log';		
		$expectedCrontabJob1->comments = 'first crontabJob';
		
		$expectedCrontabJob2 = new CrontabJob();
		$expectedCrontabJob2->minutes = '0';
		$expectedCrontabJob2->hours = '0';
		$expectedCrontabJob2->dayOfMonth = '28-31';
		$expectedCrontabJob2->months = '*';
		$expectedCrontabJob2->dayOfWeek = '*';
		$expectedCrontabJob2->taskCommandLine = '[ `/bin/date +\%d` -gt `/bin/date +\%d -d "1 day"` ] && df >> /tmp/df.log';		
		$expectedCrontabJob2->comments = 'second crontabJob';

		$expectedCrontabJob3 = new CrontabJob();
		$expectedCrontabJob3->shortCut = 'hourly';
		$expectedCrontabJob3->taskCommandLine = 'df > /tmp/df_`date +\%d_\%m_\%Y_\%H_\%M`.log';		
		$expectedCrontabJob3->comments = 'third crontabJob';

		$expectedCrontabJobs = array($expectedCrontabJob1, $expectedCrontabJob2, $expectedCrontabJob3);
		
		$crontabRepository = new CrontabRepository($fakeCrontabAdapter);
		$crontabJobs = $crontabRepository->getJobs();
			
		$this->assertEquals($expectedCrontabJobs, $crontabJobs);
		
	}
	
	/**
	 * Test if the headers Comments of the crontab file are read
	 */	
	public function testReadHeaderComments() {
		
		/* Create fake crontabAdapter */
		$fakeCrontabAdapter = $this->getMock('CrontabAdapter');
		$fakeCrontabAdapter
			->expects($this->any())
			->method('readCrontab')
			->will($this->returnValue(file_get_contents($this->fixturesPath . 'simple_crontab.txt')))
		;		
		
		$crontabRepository = new CrontabRepository($fakeCrontabAdapter);
		
		$this->assertEquals(file_get_contents($this->fixturesPath . 'crontab_headers.txt'), $crontabRepository->headerComments);
		
	}
	
	/**
	 * Test finding a job by a regular expression 
	 */	
	public function testFindJobByRegex() {
		
		/* Create fake crontabAdapter */
		$fakeCrontabAdapter = $this->getMock('CrontabAdapter');
		$fakeCrontabAdapter
			->expects($this->any())
			->method('readCrontab')
			->will($this->returnValue(file_get_contents($this->fixturesPath . 'simple_crontab.txt')))
		;
		
		$crontabJob = new CrontabJob();
		$crontabJob->minutes = '30';
		$crontabJob->hours = '23';
		$crontabJob->dayOfMonth = '*';
		$crontabJob->months = '*';
		$crontabJob->dayOfWeek = '*';
		$crontabJob->taskCommandLine = 'launch -param mycommand';		
		
		$expectedCrontabJobs = array($crontabJob);
		
		$crontabRepository = new CrontabRepository($fakeCrontabAdapter);		
		$crontabJobs = $crontabRepository->findJobByRegex('/launch -param mycommand/');
			
		$this->assertEquals($expectedCrontabJobs, $crontabJobs);
				
	}
	
	/**
	 * This test will modify an existing job and append a new job to the crontab.
	 */
	public function testPersist() {

		/* Create fake crontabAdapter */
		$fakeCrontabAdapter = $this->getMock('CrontabAdapter');
		
		$fakeCrontabAdapter
			->expects($this->any())
			->method('readCrontab')
			->will($this->returnValue(file_get_contents($this->fixturesPath . 'simple_crontab.txt')))			
		;
		
		$fakeCrontabAdapter			
			->expects($this->once())
			->method('writeCrontab')
			->with($this->equalTo(file_get_contents($this->fixturesPath . 'testing_persisted_crontab.txt')))
		;
		
		$crontabRepository = new CrontabRepository($fakeCrontabAdapter);
		
		/* Modify the existing job */
		$crontabJobs = $crontabRepository->findJobByRegex('/launch -param mycommand/');
		$crontabJobs[0]->minutes = '01';
		$crontabJobs[0]->hours = '05';
		
		/* Append new job */
		$newCrontabJob = new CrontabJob();
		$newCrontabJob->minutes = '30';
		$newCrontabJob->hours = '23';
		$newCrontabJob->taskCommandLine = 'df >> /tmp/df.log';
		$newCrontabJob->comments = 'new crontab job';
		
		$crontabRepository->addJob($newCrontabJob);
		
		$crontabRepository->persist();
		
	}
	
	/**
	 * Test if pass a wrong regular expression when searching by regex throw an invalid regex exception
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage Not a valid Regex : preg_match(): No ending delimiter '/' found
	 */
	public function testExceptionInvalidRegexOnFindJobByRegex() {

		$fakeCrontabAdapter = $this->getMock('CrontabAdapter');
		
		$fakeCrontabAdapter
			->expects($this->any())
			->method('readCrontab')
			->will($this->returnValue(file_get_contents($this->fixturesPath . 'simple_crontab.txt')))
		;		
		$crontabRepository = new CrontabRepository($fakeCrontabAdapter);
		
		$crontabRepository->findJobByRegex('/$');
		
	}
	
}	