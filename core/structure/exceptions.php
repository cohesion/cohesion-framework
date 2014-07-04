<?php

class InvalidClassException extends Exception {}
class NotFoundException extends RuntimeException {}
class DataAccessException extends RuntimeException {}
class CacheException extends DataAccessException {}

// User Safe Exceptions are exceptions where the message is safe to display to the user
class UserSafeException extends RuntimeException {}
class UnauthroizedException extends UserSafeException {}
class MissingAssetException extends UserSafeException {}

