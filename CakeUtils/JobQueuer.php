<?php

    namespace Sevenedge\CakeUtils;

	use App\Model\Entity\Job;
	use Cake\Core\App;
	use Cake\ORM\TableRegistry;

	class JobQueuer {


		public static function queue($task, $params = array(), $maxtime = 600) {
			$jobs = TableRegistry::get('Jobs');
			$data = $jobs->newEntity();
			$data->task = $task;
			$data->params = serialize($params);
			$data->maxtime = $maxtime;
			$jobs->save($data);
		}

		public static function isEmpty() {
			$jobs = TableRegistry::get('Jobs');
			$res = $jobs->find('all', array('conditions' => 'executed IS NULL'))->first();
			return empty($res);
		}

		/**
		 * We're only doing one job at a time. This to prevent really long execution times
		 */
		public static function handle() {
			$jobs = TableRegistry::get('Jobs');
			$now = time();

			$todos = $jobs->find('all', array('conditions' =>
				array('OR' =>
					array(
						'started IS NULL',
						'AND' => array(
							'UNIX_TIMESTAMP(started) + maxtime < ' . $now,
							'executed IS NULL'
						)

				)),

				'order' => array('id' =>'ASC')
			))->toArray();


			foreach ($todos as $todo) {
				if (!empty($todo)) {

					$todo->started = date('Y-m-d H:i:s');
					$jobs->save($todo);
					try {
						$params = unserialize($todo->params);
						$params = $params ? $params : array();

						$type = 'coremethod';

						$task = explode(".", $todo->task);

						if (count($task) > 1) {
							$type = 'instance';
						}
						else {
							$task = explode("::", $todo->task);
							if (count($task) > 1) {
								$type = 'static';
							}
						}

						// We might have to include some files here.
						if (in_array($type, array('instance', 'static'))) {
							// it's a class. and it's not static!
							$class = array_shift($task);

						}
						$task = array_shift($task);

						$doContinue = false;

						switch ($type) {
							case 'static':
								$doContinue = (!call_user_func_array($class . "::" . $task, $params)) ? true : false;
								break;
							case 'instance':
								//TODO might want to add some paramters for the constructor in the future, right? Even though not needed in this project.
								// HOWTO: split up params. make it an assoc array with 'init' and 'call' params.
								$instance = new $class();
								call_user_func_array(array(
									$instance,
									$task
								), $params);
								break;
							case 'coremethod':
								call_user_func_array($task, $params);
								break;
							default:
								throw new NotImplementedException("can't find that job:" . print_r($todo, 1));
						}
						
						// If we cant login to the server,
						// go to next job
						if ($doContinue) {
							continue;
						}

						$todo->executed = date('Y-m-d H:i:s');
						$jobs->save($todo);
					} catch (Exception $e) {
						$todo->started = NULL;
						$jobs->save($todo);
					}
				}
			}
		}

	}