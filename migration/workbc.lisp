(in-package #:pgloader.transforms)

(defun not-available-to-null (string)
    "Transform values 'NA', 'N/A', '-', blank to NULL."
    (cond
        ((equalp "na" string) nil)
        ((equalp "n/a" string) nil)
        ((string= "-" string) nil)
        ((string= "" (string-trim '(#\Space) string)) nil)
        (t string)
    )
)

(defun trim-hash (string)
    "Remove leading hash # character."
    (string-left-trim "#" string)
)
