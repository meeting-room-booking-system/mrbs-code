import {ok, forbidden, serverError} from 'wix-http-functions';
import {authentication} from 'wix-members-backend';
import wixData from 'wix-data';
import {contacts} from 'wix-crm-backend';
import wixSecretsBackend from 'wix-secrets-backend';


// Validates that a request is valid, ie that the requesting server has used
// a valid API key, ie one that matches the one held in the Wix secrets manager.
// Parameters:
//      request     the request
//      data        the data in the request which must include
//                      key         the API key
//                      secret_name the name of the secret in the Wix secrets manager that holds the API key
function validateRequest(request, data) {

  return wixSecretsBackend.getSecret(data.secret_name)
    .then((secret) => {
      if (secret === data.key) {
        return true;
      }
      else {
        console.log("MRBS: invalid API key passed by IP address " + request.headers['x-real-ip']);
        return false;
      }
    })
    .catch((error) => {
      console.error(error);
      return false;
    })
}


// The exported functions work by firing off two promises in parallel: the first
// validates that the request comes from an authorised server and the second does
// the actual work.  When the two promises have been resolved or rejected, this
// function processes the promise results and issues the appropriate response.
function processPromiseResults(promiseResults) {

  let result = {
    "headers": {
      "Content-Type": "application/json"
    }
  }

  if ((promiseResults[0].status === 'rejected') || (promiseResults[1].status === 'rejected')) {
    result.body = "internal server error";
    return serverError(result);
  }
  else if (promiseResults[0].value === false) {
    result.body = "forbidden";
    return forbidden(result);
  }
  else {
    result.body = promiseResults[1].value;
    return ok(result);
  }
}


// Validates a member's email login and password.  Returns a boolean.
// Request data parameters:
//      email       the member's login email address
//      password    the password
export async function post_validateMember(request) {

  const data = await request.body.json();

  const validateRequestPromise = validateRequest(request, data);

  const validateMemberPromise = authentication.login(data.email, data.password)
    .then(() => {
      return true;
    })
    .catch((error) => {
      // If the email address and password are not valid then we will get
      // an UNAUTHORIZED error.  If it's any other kind then log it.
      if (error.details.applicationError.code === "UNAUTHORIZED") {
        console.error(error);
      }
      // Return false whatever the error
      return false;
    });

  return Promise.allSettled([validateRequestPromise, validateMemberPromise])
    .then((promiseResults) => {
      return processPromiseResults(promiseResults);
    })
}


// Gets a member's details given an email address.  Returns a JSON object or NULL.
// Request data parameters:
//      email       the member's login email address
export async function post_getMemberByEmail(request) {

  const data = await request.body.json();

  const options = {
    "suppressAuth": true,
    "suppressHooks": true
  };

  const validateRequestPromise = validateRequest(request, data);

  const getMemberPromise = wixData.query("Members/PrivateMembersData")
    .eq("loginEmail", data.email)
    .limit(1)
    .find(options)
    .then((members) => {
      if(members.items.length > 0) {
        let member = members.items[0];
        // Now we've got the member we have to get their full details (including
        // custom fields, which aren't in PrivateMembersData) from Contacts using
        // the contactId.
        return contacts.getContact(member.contactId, {suppressAuth: true})
          .then((contact) => {
            return {
              member: member,
              contact: contact
            };
          })
          .catch((error) => {
            console.error(error);
            return null;
          });
      }
      else {
        return null;
      }
    })
    .catch((error) => {
      console.error(error);
      return null;
    });

  return Promise.allSettled([validateRequestPromise, getMemberPromise])
    .then((promiseResults) => {
      return processPromiseResults(promiseResults);
    })
}


// Returns an array of members indexed by 'username' and 'display_name'
// Request data parameters:
//      limit       (optional) the limit to be used in each query.  Defaults to 50.
export async function post_getMemberNames(request) {

  const data = await request.body.json();

  const options = {
    "suppressAuth": true,
    "suppressHooks": true
  };

  const defaultLimit = 50;
  let memberNames = [];
  let limit = defaultLimit;

  if (data.limit !== undefined) {
    limit = parseInt(data.limit, 10);
    if (isNaN(limit) || (limit <= 0)) {
      limit =defaultLimit;
    }
  }

  function extractMemberNames(items) {

    let result = [];

    items.forEach(function(item) {
      result.push({
        username: item.loginEmail,
        display_name: ((item.name === undefined) || (item.name === null) || (item.name === '')) ? item.loginEmail : item.name
      });
    });

    return result;
  }

  const validateRequestPromise = validateRequest(request, data);

  const getMemberNamesPromise = wixData.query("Members/PrivateMembersData")
    .limit(limit)
    .find(options)
    .then(async (results) => {
      memberNames = memberNames.concat(extractMemberNames(results.items));
      while (results.hasNext()) {
        results = await results.next();
        memberNames = memberNames.concat(extractMemberNames(results.items));
      }
    })
    .catch((error) => {
      console.error(error);
    })
    .then(() => {
      return memberNames;
    })

  return Promise.allSettled([validateRequestPromise, getMemberNamesPromise])
    .then((promiseResults) => {
      return processPromiseResults(promiseResults);
    })
}
