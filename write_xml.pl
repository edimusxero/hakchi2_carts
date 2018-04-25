#!/usr/bin/perl

##################################
#   authored: Ed Dunn
#   date    : 2018-04-25
#
#   description: Just a script to dump the database into an xml file
#
##################################

use warnings;
use strict;
use LWP::Simple;
use XML::LibXML;
use List::Util;
use Data::Dumper;
use DBI;

my $host        = '';
my $user        = '';
my $password    = '';
my $port        = '';
my $database    = '';

my $dsn = "DBI:mysql:database=$database;host=$host;port=$port;mysql_ssl=1";

my $database_handle = DBI->connect($dsn,$user,$password,{RaiseError => 1});

# Console name loaded from database
my $console = 'Nintendo Game Boy';

my $query =<<EOF;
SELECT 
    a.GameTitle,
    a.Players,
    a.`Co-op`,
    e.crc32,
    a.ReleaseDate,
    b.Publisher,
    c.Region,
    a.Art,
    a.Id,
    d.Name
FROM
    games a,
    assc_pub b,
    assc_region c,
    console d,
    assc_crc e
WHERE
    a.Publisher = b.Id AND
    Art != '' AND
    e.Region = c.Id AND
    a.Console = d.Id AND
    d.Name = ? AND
    e.Id = a.Id
ORDER BY
    GameTitle asc;
EOF

my $data = $database_handle->prepare($query);

$data->execute($console);

# Name of the exported xml file, this of course should be converted to a command line parameter but this was a quick lazy script so oh well.
my $xml_file = 'gameboycarts.xml';

open my $fh, '>', $xml_file or die "Failed to open file!: $!\n";

my $header =<<EOF;
<?xml version="1.0" encoding="UTF-8"?>
<Data>
EOF

print $fh $header;

while ( my @meta_data = $data->fetchrow_array() ) {
    my($title,$players,$sim,$crc,$release,$pub,$region,$art,$id) = @meta_data;
    
    $title =~ s/&/&amp;/g;

    my $game_info =<<EOF;
    <Game>
        <name>$title</name>
        <players>$players</players>
        <simultaneous>$sim</simultaneous>
        <crc>$crc</crc>
        <date>$release</date>
        <publisher>$pub</publisher>
        <region>$region</region>
        <cover>$art</cover>
        <api_id>$id</api_id>
    </Game>
EOF
    print $fh $game_info;
}

print $fh "</Data>";
close $fh;