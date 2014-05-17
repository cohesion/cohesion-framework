<?

class DBException extends RuntimeException {}

// User Safe Exceptions are exceptions where the message is safe to display to the user
class UserSafeException extends RuntimeException {}
class UnauthroisedException extends UserSafeException {}
class MissingAssetException extends UserSafeException {}
