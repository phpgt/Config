<?php
namespace GT\Config;

enum Environment:string {
	case DEVELOPMENT = "development";
	case STAGING = "staging";
	case PRODUCTION = "production";
}
