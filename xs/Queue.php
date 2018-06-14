<?php
/**
 * 消息列队
 * @author xs
 */
namespace xs;

class Queue{
	/**
	 * Queue Class
	 * 需要安装kafka http://kafka.apache.org/downloads.html
	 * 依赖 composer nmred/kafka-php:0.2.*
	 */
	/**
	 * 配置
	 * @var array
	 */
	private static $config = [
		//通用配置
		'clientId'                  => 'kafka-php',
		'brokerVersion'             => '0.10.1.0',
		'metadataBrokerList'        => '',
		'messageMaxBytes'           => '1000000',
		'metadataRequestTimeoutMs'  => '60000',
		'metadataRefreshIntervalMs' => '300000',
		'metadataMaxAgeMs'          => -1,
		//producer配置
		'requiredAck'               => 1,
		'timeout'                   => 5000,
		'isAsyn'                    => false,
		'requestTimeout'            => 6000,
		'produceInterval'           => 100,
		//consumer配置
		'groupId'                   => '',
		'sessionTimeout'            => 30000,
		'rebalanceTimeout'          => 30000,
		'topics'                    => array(),
		'offsetReset'               => 'latest', // earliest
		'maxBytes'                  => 65536, // 64kb
		'maxWaitTime'               => 100,
	];
}
