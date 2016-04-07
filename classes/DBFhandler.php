<?php

/**
 * Handler to manage access to dBase y FoxPro databases
 * Based on Prodigy_DBF class
 * Howto use it:
  $Test = new DBFhandler("customer.DBF", "customer.FPT");
  while(($Record = $Test->GetNextRecord(true)) and !empty($Record)) {
        print_r($Record);
  }
 */
class DBFhandler {

    private $Filename, $DB_Type, $DB_Update, $DB_Records, $DB_FirstData, $DB_RecordLength, $DB_Flags, $DB_CodePageMark, $DB_Fields, $FileHandle, $FileOpened;
    private $Memo_Handle, $Memo_Opened, $Memo_BlockSize;

    private function Initialize() {

        if ( $this->FileOpened ) {
            fclose( $this->FileHandle );
        }

        if ( $this->Memo_Opened ) {
            fclose( $this->Memo_Handle );
        }

        $this->FileOpened = false;
        $this->FileHandle = NULL;
        $this->Filename = NULL;
        $this->DB_Type = NULL;
        $this->DB_Update = NULL;
        $this->DB_Records = NULL;
        $this->DB_FirstData = NULL;
        $this->DB_RecordLength = NULL;
        $this->DB_CodePageMark = NULL;
        $this->DB_Flags = NULL;
        $this->DB_Fields = array();

        $this->Memo_Handle = NULL;
        $this->Memo_Opened = false;
        $this->Memo_BlockSize = NULL;

    }

    public function __construct( $Filename, $MemoFilename = NULL ) {
        $this->DBFhandler( $Filename, $MemoFilename );

    }

    public function DBFhandler( $Filename, $MemoFilename = NULL ) {
        $this->Initialize();
        $this->OpenDatabase( $Filename, $MemoFilename );

    }

    public function OpenDatabase( $Filename, $MemoFilename = NULL ) {
        $Return = false;
        $this->Initialize();

        $this->FileHandle = fopen( $Filename, "r" );
        if ( $this->FileHandle ) {
            // DB Open, reading headers
            $this->DB_Type = dechex( ord( fread( $this->FileHandle, 1 ) ) );
            $LUPD = fread( $this->FileHandle, 3 );
            $this->DB_Update = ord( $LUPD[ 0 ] ) . "/" . ord( $LUPD[ 1 ] ) . "/" . ord( $LUPD[ 2 ] );
            $Rec = unpack( "V", fread( $this->FileHandle, 4 ) );
            $this->DB_Records = $Rec[ 1 ];
            $Pos = fread( $this->FileHandle, 2 );
            $this->DB_FirstData = (ord( $Pos[ 0 ] ) + ord( $Pos[ 1 ] ) * 256);
            $Len = fread( $this->FileHandle, 2 );
            $this->DB_RecordLength = (ord( $Len[ 0 ] ) + ord( $Len[ 1 ] ) * 256);
            fseek( $this->FileHandle, 28 ); // Ignoring "reserved" bytes, jumping to table flags
            $this->DB_Flags = dechex( ord( fread( $this->FileHandle, 1 ) ) );
            $this->DB_CodePageMark = ord( fread( $this->FileHandle, 1 ) );
            fseek( $this->FileHandle, 2, SEEK_CUR );    // Ignoring next 2 "reserved" bytes
            // Now reading field captions and attributes
            while ( !feof( $this->FileHandle ) ) {

                // Checking for end of header
                if ( ord( fread( $this->FileHandle, 1 ) ) == 13 ) {
                    break;  // End of header!
                } else {
                    // Go back
                    fseek( $this->FileHandle, -1, SEEK_CUR );
                }

                $Field[ "Name" ] = trim( fread( $this->FileHandle, 11 ) );
                $Field[ "Type" ] = fread( $this->FileHandle, 1 );
                fseek( $this->FileHandle, 4, SEEK_CUR );  // Skipping attribute "displacement"
                $Field[ "Size" ] = ord( fread( $this->FileHandle, 1 ) );
                fseek( $this->FileHandle, 15, SEEK_CUR ); // Skipping any remaining attributes
                $this->DB_Fields[] = $Field;
            }

            // Setting file pointer to the first record
            fseek( $this->FileHandle, $this->DB_FirstData );

            $this->FileOpened = true;

            // Open memo file, if exists
            if ( !empty( $MemoFilename ) and file_exists( $MemoFilename ) and preg_match( "%^(.+).fpt$%i", $MemoFilename ) ) {
                $this->Memo_Handle = fopen( $MemoFilename, "r" );
                if ( $this->Memo_Handle ) {
                    $this->Memo_Opened = true;

                    // Getting block size
                    fseek( $this->Memo_Handle, 6 );
                    $Data = unpack( "n", fread( $this->Memo_Handle, 2 ) );
                    $this->Memo_BlockSize = $Data[ 1 ];
                }
            }
        }

        return $Return;

    }

    public function GetNextRecord( $FieldCaptions = false ) {
        $Return = NULL;
        $Record = array();

        if ( !$this->FileOpened ) {
            $Return = false;
        } elseif ( feof( $this->FileHandle ) ) {
            $Return = NULL;
        } else {
            // File open and not EOF
            fseek( $this->FileHandle, 1, SEEK_CUR );  // Ignoring DELETE flag
            foreach ( $this->DB_Fields as $Field ) {
                $RawData = fread( $this->FileHandle, $Field[ "Size" ] );
                // Checking for memo reference
                if ( $Field[ "Type" ] == "M" and $Field[ "Size" ] == 4 and ! empty( $RawData ) ) {
                    // Binary Memo reference
                    $Memo_BO = unpack( "V", $RawData );
                    if ( $this->Memo_Opened and $Memo_BO != 0 ) {
                        fseek( $this->Memo_Handle, $Memo_BO[ 1 ] * $this->Memo_BlockSize );
                        $Type = unpack( "N", fread( $this->Memo_Handle, 4 ) );
                        if ( $Type[ 1 ] == "1" ) {
                            $Len = unpack( "N", fread( $this->Memo_Handle, 4 ) );
                            $Value = trim( fread( $this->Memo_Handle, $Len[ 1 ] ) );
                        } else {
                            // Pictures will not be shown
                            $Value = "{BINARY_PICTURE}";
                        }
                    } else {
                        $Value = "{NO_MEMO_FILE_OPEN}";
                    }
                } else {
                    $Value = trim( $RawData );
                }

                if ( $FieldCaptions ) {
                    $Record[ $Field[ "Name" ] ] = $Value;
                } else {
                    $Record[] = $Value;
                }
            }

            $Return = $Record;
        }

        return $Return;

    }

    function __destruct() {
        // Cleanly close any open files before destruction
        $this->Initialize();

    }

}