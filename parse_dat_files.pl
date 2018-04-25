#!/usr/bin/perl

##################################
#   authored: Ed Dunn
#   date    : 2018-04-25
#
#   description: attempts to match loaded data from thegamesdb; which have been loaded into a database; with dat files from http://datomatic.no-intro.org/.  This is a work in progress and is far from perfect and requires some manual intervention to match games that may not be loaded from this script.
#
##################################

use warnings;
use strict;
use LWP::Simple;
use XML::LibXML;
use List::Util;
use Data::Dumper;
use DBI;
use List::Compare;

my $host        = '';
my $user        = '';
my $password    = '';
my $port        = '';
my $database    = '';

my $dsn = "DBI:mysql:database=$database;host=$host;port=$port;mysql_ssl=1";

my $database_handle = DBI->connect($dsn,$user,$password,{RaiseError => 1});

# This needs to be te name of the console as shown in the console table
my $console = "Nintendo Game Boy";

my $sql = "SELECT Id, GameTitle FROM games WHERE Console = (SELECT Id FROM console WHERE Name = '$console') AND Art != ''";
my @sega_games = @{$database_handle->selectall_arrayref($sql)};

# Name of dat file
my $file = "game_boy.dat";

my $dom = XML::LibXML->load_xml(location => $file);

my %game_hash;

foreach my $title ($dom->findnodes('/datafile/game')) {
    my $crc = $title->findvalue('./rom/@crc');
    my $game = $title->to_literal();
    $game =~ s/^\s+|\s+$//g;
    $game_hash{$game} = $crc;
    
    check_game($crc,$game,$console);
}

my @game_list;
my @verified;

foreach my $item(@sega_games){
    my ($game_id,$sega) = @$item;    
    $sega =~ s/: / - /g;
    $sega =~ s/! /! - /g;
    $sega =~ s/^Disneys //g;
    $sega =~ s/^The //g;
    
    my $game_map = $database_handle->prepare("SELECT * from crc WHERE Title LIKE ? AND console = (SELECT Id FROM console WHERE Name = ?)");
    $game_map->execute('%' . $sega . '%',$console) or die "Cannot execute the query :$!";

    while ( my @segaList = $game_map->fetchrow_array() ) {
        my ($id,$title,$sega_crc ) = @segaList;
        
        my $region = get_region($title);
        print "$id | $title | $sega_crc | $region\n";            
        $title =~ s/ \(.+$//g;
        $title =~ s/(\w+)/\u$1/g;
        $title =~ s/, The+//g;
        $title =~ s/'//g;
        $title =~ s/&amp;/&/g;
        $sega =~ s/'//g;
        $sega =~ s/(\w+)/\u$1/g;

        my $sega_lc = lc($sega);
        my $title_lc = lc($title);

        push @game_list, ("$sega|$title|$sega_crc");
            
        if($sega_lc eq $title_lc){
            push @verified, ("$sega|$title|$sega_crc");
            my $ins = $database_handle->prepare("INSERT IGNORE INTO assc_crc (Id,crc32,Region) VALUE (?,?,?)"); 
            $ins->execute($game_id,$sega_crc,$region);
        }
    }
}

my $verified_list = 'verified.txt';
my $all_list = 'all.txt';

my $diff = List::Compare->new(\@game_list, \@verified);

my @extra = $diff->get_Lonly; 
my @missing = $diff->get_Ronly;


# Writes files, one of confirmed names where they match and another file where there are many matches or that are close. If there are more than 1 match or it's close, it is not loaded into the database. I had to manually intervene, this script is far from perfect but it got me going.
open my $vfh, '>', $verified_list;
print $vfh join("\n", sort(@missing));
close $vfh;

open my $afh, '>', $all_list;
print $afh join("\n", sort(@extra));
close $afh;

sub get_region{
    my $region_tmp = shift;
    
    my ($region) = $region_tmp =~ /\(([^\)]+){1}\)/;
    
    my $region_select = $database_handle->prepare('SELECT Id FROM assc_region WHERE Region = ?');
    $region_select->execute($region);
    my ($reg_id) = $region_select->fetchrow_array();
    
    if(!$reg_id){
        my $reg_insert = $database_handle->prepare('INSERT INTO assc_region (Region) VALUES (?)');
        $reg_insert->execute($region);
        $reg_id = $reg_insert->{mysql_insertid};
    }
    
    return $reg_id;
}

sub check_game{
    my ($crc,$game,$console) = @_;
    
    my $crc_select = $database_handle->prepare('SELECT Id FROM crc WHERE crc = ?');
    $crc_select->execute($crc);
    my ($num) = $crc_select->fetchrow_array();
    
    if(!$num){
        my $crc_insert = $database_handle->prepare("INSERT INTO crc (Title, crc, console) VALUES (?,?,(SELECT Id FROM console WHERE Name = ?))");
        $crc_insert->execute($game,$crc,$console);
    }
 }   