#include <stdio.h>
#include "valid.h"

int main(int argc, char ** argv)
{
  char * user, * pass, * server, * backup, * domain, * match;
  
  if(argc < 6)
    return 1;
  
  user   = argv[1];
  pass   = argv[2];
  server = argv[3];
  backup = argv[4];

  /* Handle alternate domains, if we have a '/' in the username, use the
     group specified before the '/', instead of argument 5 */
  match = strchr(user, '/');
  if (match)
  {
    *match = '\0';
    domain = user;
    user = match+1;
  }
  else
  {
    domain = argv[5];
  }
  
  if(Valid_User(user, pass, server, backup, domain) == NTV_NO_ERROR)
    return 0;
  
  return 1;
}
