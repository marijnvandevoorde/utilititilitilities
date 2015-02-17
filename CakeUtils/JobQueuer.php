<?php

    namespace Sevenedge\CakeUtils;

	App::uses('Job', 'Model');

	class JobQueuer {


		public static function queue($task, $params = array(), $maxtime = 600) {
			$Job = new Job();
			$data = $Job->create();
			$data['Job']['task'] = $task;
			$data['Job']['params'] = serialize($params);
			$data['Job']['maxtime'] = $maxtime;

			$Job->save($data);
		}

		public static function isEmpty() {
			$Job = new Job();
			$res = $Job->find('first', array('conditions' => 'executed IS NULL'));
			return empty($res);
		}

		/**
		 * We're only doing one job at a time. This to prevent really long execution times
		 */
		public static function handle() {
			$Job = new Job();
			$now = time();

			$todo = $Job->find('first', array('conditions' =>
				array('OR' =>
					array(
						'Job.started' => null,
						/*
						'AND' => array(
							'UNIX_TIMESTAMP(Job.started) + Job.maxtime < ' . $now,
							'Job.executed' => null
						)
						*/
				)),

				'order' => array('Job.id ASC')
			));


			if(!empty($todo)) {
				$todo['Job']['started'] = date('Y-m-d H:i:s');
				$Job->save($todo);
				try {
					$params = unserialize($todo['Job']['params']);
					$params = $params? $params : array();

					$type = 'coremethod';

					$task = explode(".", $todo['Job']['task']);
					if (count($task) > 1) {
						$type = 'instance';
					} else {
						$task = explode("::", $todo['Job']['params']);
						if (count($task) > 1) {
							$type = 'static';
						}
					}

					// We might have to include some files here.
					if (in_array($type, array('instance', 'static'))) {
						// it's a class. and it's not static!
						$class = array_shift($task);
						$class = explode("/", $class);
						$className = array_pop($class);
						if (count($class) > 0) {
							// it's some lib
							$path = implode('/', $class);
							App::uses($className, $path);
						}
					}
					$task = array_shift($task);
					switch ($type) {
						case 'static':
							call_user_func_array($className . "::" . $task, $params);
							break;
						case 'instance':
							//TODO might want to add some paramters for the constructor in the future, right? Even though not needed in this project.
                            // HOWTO: split up params. make it an assoc array with 'init' and 'call' params.
							$instance = new $className();
							call_user_func_array(array($instance, $task), $params);
							break;
						case 'coremethod':
							call_user_func_array($task, $params);
							break;
						default:
							throw new NotImplementedException("can't find that job:" . print_r($todo, 1));
					}

					$todo['Job']['executed'] = date('Y-m-d H:i:s');
					$Job->save($todo);
				}
				catch (Exception $e) {
					$todo['Job']['started'] = null;
					$Job->save($todo);
					throw $e;
				}
			}
		}

	}