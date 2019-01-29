<?php
/**
 * Created by naveedulhassan.
 * Date: 1/22/16
 * Time: 2:55 PM
 */

namespace App\Generator\src;


class DbOracle
{
    public static function fields($table)
    {
        $columns = \DB::select("SELECT  column_name, data_type, data_length,nullable FROM user_tab_columns where table_name = '".strtoupper($table)."'");

        // $pdo = \DB::connection()->getPdo();
        // $columns = $pdo->query("desc $table")->fetchAll();
        // dd($columns);

        $tableFields = array(); // return value
        foreach ($columns as $column) {
            $column = (array)$column;
            $field = new Column();
            $field->name = strtolower($column['column_name']);
            $field->defValue = NULL;
            $field->required = $column['nullable'] == 'N';
            if($field->name=="id"){
              $field->key = "PRI";
            }else{
              $field->key = NULL;
            }
            // type and length
            $field->maxLength = intval($column['data_length']);// get field and type from $res['Type']
            $type_length = explode( "(", $column['data_type'] );
            $field->type = strtolower($type_length[0]);
            // dd($field);
            // everything decided for the field, add it to the array
            $tableFields[$field->name] = $field;
            // var_dump($field);
        }
        return $tableFields;
    }

    public static function getConditionStr($field)
    {
        if( in_array( $field->type, ['varchar','text'] ) )
            return "'{$field->name}','like','%'.\Request::input('{$field->name}').'%'";
        return "'{$field->name}',\Request::input('{$field->name}')";
    }

    public static function getValidationRule($field)
    {
        // skip certain fields
        if ( in_array( $field->name, static::skippedFields() ) )
            return "";

        $rules = [];
        // required fields
        if( $field->required )
            $rules[] = "required";

        // strings
        if( in_array( $field->type, ['varchar','varchar2','text'] ) )
        {
            $rules[] = "string";
            if ( $field->maxLength ) $rules[] = "max:".$field->maxLength;
        }

        // dates
        if( in_array( $field->type, ['date','datetime','timestamp'] ) )
            $rules[] = "date";

        // numbers
        if ( in_array( $field->type, ['number','unsigned_int'] ) )
            $rules [] = "integer";

        // emails
        if( preg_match("/email/", $field->name) ){ $rules[] = "email"; }

        // enums
        if ( $field->type == 'enum' )
            $rules [] = "in:".join( ",", $field->enumValues );

        return "'".$field->name."' => '".join( "|", $rules )."',";
    }

    public static function skippedFields()
    {
        return ['id','created_at','updated_at','deleted_at'];
    }

    public static function isGuarded($fieldName)
    {
        return in_array( $fieldName, static::skippedFields() );
    }

    public static function getSearchInputStr ( $field )
    {
        // selects
        if ( $field->type == 'enum' )
        {
            $output = "{!!\App\Generator\src\Html::selectRequested(\n";
            $output .= "\t\t\t\t\t'".$field->name."',\n";
            $output .= "\t\t\t\t\t[ '', '".join("', '",$field->enumValues)."' ],\n"; //Yes', 'No
            $output .= "\t\t\t\t\t['class'=>'form-control']\n";
            $output .= "\t\t\t\t)!!}";
            return $output;
        }

        // input type:
        $type = 'text';
        $kelas = "";
        if ( $field->type == "date" ){
          $type = $field->type;
          $kelas = "tanggal-picker";
        }


        $output = '<input type="'.$type.'" class="form-control '.$kelas.'" name="'.$field->name.'" value="{{Request::input("'.$field->name.'")}}">';
        return $output;

    }

    public static function getFormInputMarkup ( $field, $modelName = '' )
    {
        // skip certain fields
        if ( in_array( $field->name, static::skippedFields() ) )
            return "";

        // string that binds the model
        $modelStr = $modelName ? '->model($'.$modelName.')' : '';

        if(($field->type == 'number')&&(preg_match("/id_|_id/",$field->name,$match))){
          $fieldName = strtolower(preg_replace("/id_|_id/","",$field->name));
          // preg_match("/tref_|tm_|tr_|tconf_|td_/i", $$fieldName, $matches);
          // $retPrefix = null;
          // if(count($matches>0)){
          //   $retPrefix = str_replace("_","", (ucfirst(strtolower($matches[0]))));
          // }
          $fieldName = preg_replace("/tref_|tm_|tr_|tconf_|td_/i","",$fieldName);
          $str="{{ Form::hidden('{$field->name}',$".$modelName."->exists?$".$modelName."->{$field->name}:null,array('id'=>'{$field->name}')) }}\n";
          // $modelStr = $fieldName ? '->model($'.$modelName.'->'.$fieldName.'->name)' : '';
          $modelStr = $fieldName ? '->model(null)' : '';
          $value = "$".$modelName."->exists?(isset($".$modelName."->{$fieldName})?$".$modelName."->{$fieldName}->{$fieldName}:null):null";
          $str.="{!! \App\Generator\src\Form::autocomplete('{$fieldName}',array('value'=>$value)){$modelStr}->show() !!}";
          return $str;
        }

        // selects
        if ( $field->type == 'enum' )
        {
            return "{!! \App\Generator\src\Form::select( '{$field->name}', [ '".join("', '",$field->enumValues)."' ] ){$modelStr}->show() !!}\n";
        }

        if ( $field->type == 'text' )
        {
            return "{!! \App\Generator\src\Form::textarea( '{$field->name}' ){$modelStr}->show() !!}\n";
        }

        // input type:
        $type = 'text';
        if ( $field->type == 'timestamp' || $field->type == 'date' ) {
          return "{!! \App\Generator\src\Form::input('{$field->name}','text',['class'=>'tanggal-picker']){$modelStr}->show() !!}\n";
        }else{
          return "{!! \App\Generator\src\Form::input('{$field->name}','text'){$modelStr}->show() !!}\n";
        }
    }

}
